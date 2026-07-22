<?php


declare( strict_types=1 );

namespace BytePerfect\EDI\Parsers;


use Automattic\WooCommerce\Enums\ProductStockStatus;
use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Matchers\ProductMatcher;
use Exception;
use WC_Data_Exception;
use WC_Product;
use WC_Product_Variable;
use WC_Product_Variation;


class OffersParser {
	
	public function __construct() {
		add_action(
			'_/КоммерческаяИнформация/ПакетПредложений/Предложения/Предложение',
			array( $this, 'process' )
		);
	}

	
	public function process( DataXML $xml_object ): void {
		$product_data = $this->parse_xml_object( $xml_object );

		try {
			$product_id = ProductMatcher::locate( $product_data['guid'], $product_data['sku'], $product_data['name'] );

			if ( is_null( $product_id ) ) {
				EDI::log()->error( "Product was not found. GUID {$product_data['guid']}." );

				return;
			}

						if ( $product_data['variation_guid'] ) {
				$variation_id = ProductMatcher::locate( $product_data['variation_guid'], $product_data['sku'], $product_data['name'], $product_id );

				$this->process_variation( $product_id, $variation_id, $product_data['variation_guid'], $product_data );
			} else {
								$this->update_product( $product_id, $product_data );
			}
		} catch ( Exception $e ) {
			EDI::log()->error( "Error processing GUID {$product_data['guid']}, product ID $product_id." );
			EDI::log()->error( $e->getMessage() );
		}
	}

	
	protected function parse_xml_object( DataXML $xml_object ): array {
		$xml_data = $xml_object->GetArray();
		$xml_data = $xml_data['Предложение']['#'];

		$product_guid = explode( '#', $xml_data['Ид'][0]['#'] ?? '', 2 );

		$product_data = array(
			'guid'           => $product_guid[0] ?? '',
			'variation_guid' => $product_guid[1] ?? null,
			'name'           => $xml_data['Наименование'][0]['#'] ?? '',
			'sku'            => $xml_data['Артикул'][0]['#'] ?? '',
		);

		if ( isset( $xml_data['Цены'][0]['#']['Цена'][0]['#']['ЦенаЗаЕдиницу'][0]['#'] ) ) {
			$product_data['price'] = $xml_data['Цены'][0]['#']['Цена'][0]['#']['ЦенаЗаЕдиницу'][0]['#'];
		}
		if ( isset( $xml_data['Количество'][0]['#'] ) ) {
			$product_data['stock'] = $xml_data['Количество'][0]['#'];
		}

				return apply_filters( 'edi_parse_offer_xml_object', $product_data, $xml_data );
	}

	
	protected function update_product( $the_product, array $product_data ): void {
		$product = $this->get_valid_product( $the_product );

				if ( $product instanceof WC_Product_Variation ) {
			$this->update_variation_data( $product, $product_data );
		} elseif ( $product instanceof WC_Product_Variable ) {
			$this->update_variable_product_data( $product, $product_data );
		} else {
			$this->update_simple_product_data( $product, $product_data );
		}

		$product->set_status( 'publish' );

		do_action_ref_array( 'edi_offer_before_save', array( &$product, &$product_data ) );

		$product->save();

		EDI::log()->debug( "Product was updated. GUID {$product_data['guid']} -> ID {$product->get_id()}." );
	}

	
	protected function process_variation( int $parent_id, ?int $variation_id, string $variation_guid, array $product_data ): void {
				$parent_product = $this->ensure_variable_product( $parent_id );
				$this->update_product( $parent_product, $product_data );

				$variation = $this->get_or_create_variation( $variation_id, $parent_id );
				$this->update_product( $variation, $product_data );

				if ( ! $variation_id ) {
			ProductMatcher::update_product_match_meta(
				$variation->get_id(),
				$variation_guid,
				ProductMatcher::MATCH_SOURCE_GUID
			);
		}

				WC_Product_Variable::sync( $parent_product );

		EDI::log()->debug( "Variation was updated. GUID {$variation_guid} -> ID {$variation->get_id()}." );
	}

	
	private function ensure_variable_product( int $parent_id ): WC_Product_Variable {
		$parent_product = wc_get_product( $parent_id );
		if ( ! $parent_product instanceof WC_Product ) {
			throw new Exception( esc_html__( 'Parent product is not valid.', 'edi' ) );
		}

				if ( ! $parent_product->is_type( 'variable' ) ) {
						wp_remove_object_terms( $parent_id, 'simple', 'product_type' );

						wp_set_object_terms( $parent_id, 'variable', 'product_type', true );

						$parent_product = new WC_Product_Variable( $parent_id );
			$parent_product->save();

			EDI::log()->debug( "Product {$parent_id} converted to variable product." );
		}

		return $parent_product;
	}

	
	private function get_or_create_variation( ?int $variation_id, int $parent_id ): WC_Product_Variation {
		if ( ! $variation_id ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $parent_id );
			EDI::log()->debug( "Creating new variation for parent {$parent_id}" );

			return $variation;
		}

		$variation = wc_get_product( $variation_id );
		if ( ! $variation instanceof WC_Product_Variation ) {
			throw new Exception(
				sprintf(
				
					esc_html__( 'Variation with ID %d not found or is not a valid variation.', 'edi' ),
					(int) $variation_id
				)
			);
		}

		return $variation;
	}

	
	private function get_valid_product( $the_product ): WC_Product {
		if ( $the_product instanceof WC_Product ) {
			return $the_product;
		}

		$product = wc_get_product( $the_product );
		if ( ! $product instanceof WC_Product ) {
			throw new Exception( esc_html__( 'Is not a valid product.', 'edi' ) );
		}

		return $product;
	}

	
	private function update_simple_product_data( WC_Product $product, array $product_data ): void {
		$this->update_common_product_data( $product, $product_data );
	}

	
	private function update_variable_product_data( WC_Product_Variable $product, array $product_data ): void {
		$product->set_manage_stock( false );
	}

	
	private function update_variation_data( WC_Product_Variation $product, array $product_data ): void {
		$this->update_common_product_data( $product, $product_data );

		try {
			$product->set_sku( $product_data['sku'] );
		} catch ( WC_Data_Exception $e ) {
			EDI::log()->warning( "Error processing SKU of GUID {$product_data['guid']}. " . $e->getMessage() );
		}
	}

	
	private function update_common_product_data( WC_Product $product, array $product_data ): void {
		if ( isset( $product_data['price'] ) ) {
			$product->set_price( $product_data['price'] );
			$product->set_sale_price( $product_data['price'] );
			$product->set_regular_price( $product_data['price'] );
		}

		if ( isset( $product_data['stock'] ) ) {
			$quantity = (float) $product_data['stock'];

			$product->set_manage_stock( true );
			$product->set_stock_quantity( $quantity );
			$product->set_stock_status( $quantity ? ProductStockStatus::IN_STOCK : ProductStockStatus::OUT_OF_STOCK );
		}
	}
}
