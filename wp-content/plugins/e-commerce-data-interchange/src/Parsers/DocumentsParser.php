<?php


declare( strict_types=1 );

namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Matchers\ProductMatcher;
use Exception;
use WC_Data_Exception;
use WC_DateTime;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
use WC_Product;
use WC_Shipping_Rate;


class DocumentsParser {
	
	public function __construct() {
		add_action(
			'_/КоммерческаяИнформация/Документ',
			array( $this, 'process' )
		);
	}

	
	public function process( DataXML $xml_object ): void {
		try {
			$document_data = $this->parse_xml_object( $xml_object );

			$this->update_order( $document_data );
		} catch ( Exception $e ) {
			$guid = isset( $document_data, $document_data['guid'] )
				? ( is_string( $document_data['guid'] ) ? $document_data['guid'] : '' )
				: '';
			$id   = isset( $document_data, $document_data['id'] )
				? ( is_string( $document_data['id'] ) ? $document_data['id'] : '' )
				: '';

			EDI::log()->error(
				sprintf(
				
					__( 'Error processing GUID %1$s, order ID %2$s.', 'edi' ),
					$guid,
					$id
				)
			);
			EDI::log()->error( $e->getMessage() );
		}
	}

	
	protected function parse_xml_object( DataXML $xml_object ): array {
		$xml_data = $xml_object->GetArray();
		$xml_data = $xml_data['Документ']['#'];

		$products = array();
		$fees     = array();
		$shipping = array();
		foreach ( $xml_data['Товары'][0]['#']['Товар'] as $product_data ) {
			$product_data = $product_data['#'];

			$product_data = OrdersXMLParserUtils::parse_product_data( $product_data );

			if ( 'Товар' === $product_data['type'] ) {
				$product_id = ProductMatcher::locate( $product_data['guid'], $product_data['sku'], $product_data['name'] );

				if ( ! $product_id ) {
					throw new Exception(
						sprintf(
						
							__( 'Product is not synchronized: %s.', 'edi' ),
							$product_data['guid']
						)
					);
				}

				$products[ $product_id ] = $product_data;
			} elseif ( 'Услуга' === $product_data['type'] ) {
				if ( 0 === strpos( $product_data['name'], 'SHIPPING' ) ) {
					$shipping[ $product_data['name'] ] = $product_data;
				} else {
					$fees[ $product_data['name'] ] = $product_data;
				}
			}
		}

		$details = array();
		foreach ( $xml_data['ЗначенияРеквизитов'][0]['#']['ЗначениеРеквизита'] as $details_item ) {
			$details_item = $details_item['#'];

			$details[ $details_item['Наименование'][0]['#'] ] = $details_item['Значение'][0]['#'];
		}

				return apply_filters(
			'edi_parse_document_xml_object',
			array(
				'guid'     => $xml_data['Ид'][0]['#'] ?? '',
				'id'       => (int) ( $xml_data['Номер'][0]['#'] ?? '' ),
				'total'    => $xml_data['Сумма'][0]['#'] ?? '',
				'products' => $products,
				'fees'     => $fees,
				'shipping' => $shipping,
				'details'  => $details,
			),
			$xml_data
		);
	}

	
	protected function update_order( array $document_data ): void {
		$order = wc_get_order( $document_data['id'] );

		if ( ! $order instanceof WC_Order ) {
			throw new Exception(
				sprintf(
				
					__( 'Order does not exist: %d.', 'edi' ),
					$document_data['id']
				)
			);
		}

		$this->process_products( $document_data['products'], $order );
		$this->process_fees( $document_data['fees'], $order );
		$this->process_shipping( $document_data['shipping'], $order );

		$order->recalculate_coupons();
		$order->calculate_shipping();
		$order->calculate_totals();

		$this->update_status( $order, $document_data['details'] );

		do_action_ref_array( 'edi_order_before_save', array( &$order, &$document_data ) );

		$order->save();

		EDI::log()->debug(
			sprintf(
			
				__( 'Order was updated. GUID %1$s -> ID %2$s.', 'edi' ),
				$document_data['guid'],
				$order->get_id()
			)
		);
	}

	
	protected function process_products( array $products_data, WC_Order $order ): void {
		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			if ( isset( $products_data[ $item->get_product_id() ] ) ) {
								$this->update_order_item( $item, $products_data[ $item->get_product_id() ] );

								unset( $products_data[ $item->get_product_id() ] );
			} else {
								$order->remove_item( $item->get_id() );
			}
		}

				foreach ( $products_data as $product_id => $product_data ) {
			$this->add_order_items( $order, $product_id, $product_data );
		}
	}

	
	protected function process_fees( array $fees_data, WC_Order $order ): void {
		foreach ( $order->get_fees() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Fee ) {
				continue;
			}

			if ( isset( $fees_data[ $item->get_name() ] ) ) {
								$item->set_total( $fees_data[ $item->get_name() ]['total'] );
				$item->save();

								unset( $fees_data[ $item->get_name() ] );
			} else {
								$order->remove_item( $item->get_id() );
			}
		}

				foreach ( $fees_data as $service_name => $item ) {
			if ( 0 === strpos( $service_name, 'SHIPPING' ) ) {
				continue;
			}

			$fee = new WC_Order_Item_Fee();
			$fee->set_amount( $item['total'] );
			$fee->set_total( $item['total'] );
			$fee->set_name( $service_name );
			$order->add_item( $fee );

			unset( $fees_data[ $service_name ] );
		}
	}

	
	protected function process_shipping( array $shipping_data, WC_Order $order ): void {
		foreach ( $order->get_shipping_methods() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Shipping ) {
				continue;
			}

			$item_name = sprintf(
				'SHIPPING|%s|%s|%s',
				$item->get_instance_id(),
				$item->get_method_id(),
				$item->get_method_title()
			);

			if ( isset( $shipping_data[ $item_name ] ) ) {
								$item->set_total( $shipping_data[ $item_name ]['total'] );
				$item->save();

								unset( $shipping_data[ $item_name ] );
			} else {
								$order->remove_item( $item->get_id() );
			}
		}

		foreach ( $shipping_data as $name => $item ) {
			if ( 0 !== strpos( $name, 'SHIPPING' ) ) {
				continue;
			}

			$item_data = explode( '|', $name );
			if ( count( $item_data ) !== 4 ) {
				continue;
			}

			$item_data = (array) array_combine(
				array( 'label', 'instance_id', 'method_id', 'method_title' ),
				$item_data
			);

			$rate = new WC_Shipping_Rate(
				$item_data['method_id'],
				$item_data['method_title'],
				(float) $item['total'],
				array(),
				$item_data['method_id'],
				$item_data['instance_id']
			);

			$item = new WC_Order_Item_Shipping();
			$item->set_order_id( $order->get_id() );
			$item->set_shipping_rate( $rate );
			$order->add_item( $item );

			unset( $shipping_data[ $name ] );
		}

		if ( count( $shipping_data ) > 0 ) {
			EDI::log()->error(
				sprintf(
				
					__( 'Error processing shipping methods: %s', 'edi' ),
					wc_print_r( $shipping_data, true )
				)
			);
		}
	}

	
	protected function update_order_item( WC_Order_Item_Product $item, array $product_data ): void {
		$product = wc_get_product( $item->get_product_id() );
		if ( ! $product instanceof WC_Product ) {
			throw new Exception(
				sprintf(
				
					__( 'Product was not found: %d.', 'edi' ),
					$item->get_product_id()
				)
			);
		}

		$total = $this->get_price_excluding_tax(
			$product,
						$product_data['quantity'],
			$product_data['price'],
			$item->get_order()
		);

				$item->set_quantity( $product_data['quantity'] );
		$item->set_subtotal( (string) $total );
		$item->set_total( (string) $total );

		do_action_ref_array( 'edi_order_item_before_save', array( &$item, &$product_data ) );

		$item->save();
	}

	
	protected function add_order_items( WC_Order $order, int $product_id, array $product_data ): void {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			throw new Exception(
				sprintf(
				
					__( 'Product was not found: %d.', 'edi' ),
					$product_id
				)
			);
		}

		$total = $this->get_price_excluding_tax(
			$product,
						$product_data['quantity'],
			$product_data['price'],
			$order
		);

		$item_id = $order->add_product(
			$product,
						$product_data['quantity'],
						apply_filters(
				'edi_add_order_product_args',
				array(
					'order'    => $order,
					'subtotal' => $total,
					'total'    => $total,
				),
				$order,
				$product_id,
				$product_data
			)
		);

		do_action_ref_array( 'edi_order_item_after_save', array( &$item_id, &$product_data ) );
	}

	
	protected function get_price_excluding_tax(
		WC_Product $product,
		int $quantity,
		float $price,
		WC_Order $order
	) {
		return wc_get_price_excluding_tax(
			$product,
			array(
				'qty'   => $quantity,
				'price' => $price,
				'order' => $order,
			)
		);
	}

	
	private function update_status( WC_Order $order, array $details ): void {
		

		if ( 'true' === $details['ПометкаУдаления'] ) {
			$status = 'trash';
		} else {
			if ( wc_get_order_status_name( $details['Статус заказа'] ) === $details['Статус заказа'] ) {
								$status = 'processing';
			} else {
				$status = $details['Статус заказа'];
			}
		}

		$date = new WC_DateTime();
		$order->set_status(
					apply_filters( 'edi_order_status', $status, $order, $details ),
			'Статус заказа изменен в результате синхронизации ' . $date->date( 'Y-m-d H:i:s' )
		);
	}
}
