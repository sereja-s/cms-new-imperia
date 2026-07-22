<?php


declare( strict_types=1 );

namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Matchers\ProductMatcher;
use BytePerfect\EDI\Utils;
use Exception;
use WC_Data_Exception;
use WC_Product;


class SaleProductsParser {
	
	public function __construct() {
		add_action(
			'_/КоммерческаяИнформация/Документ/Товары/Товар',
			array( $this, 'process' )
		);
	}

	
	public function process( DataXML $xml_object ): void {
		$product_data = $this->parse_xml_object( $xml_object );

		if ( 'Товар' !== $product_data['type'] ) {
			return;
		}

		try {
						$product_id = ProductMatcher::locate( $product_data['guid'], $product_data['sku'], $product_data['name'] );

			if ( ! $product_id ) {
				$product_id = $this->create_product( $product_data );

				if ( $product_id ) {
					ProductMatcher::update_product_match_meta(
						$product_id,
						$product_data['guid'],
						ProductMatcher::MATCH_SOURCE_GUID
					);
				}
			}
		} catch ( Exception $e ) {
			EDI::log()->error(
				sprintf(
				
					__( 'Error processing GUID %1$s, product ID %2$s.', 'edi' ),
					$product_data['guid'],
					$product_id ?? 'undefined'
				)
			);
			EDI::log()->error( $e->getMessage() );
		}
	}

	
	protected function parse_xml_object( DataXML $xml_object ): array {
		$xml_data = $xml_object->GetArray();
		$xml_data = $xml_data['Товар']['#'];

		return OrdersXMLParserUtils::parse_product_data( $xml_data );
	}

	
	protected function create_product( array $product_data ): int {
		$product = new WC_Product();

		$product->set_name( $product_data['name'] );
		try {
			$product->set_sku( $product_data['sku'] );
		} catch ( WC_Data_Exception $e ) {
			EDI::log()->warning(
				sprintf(
				
					__( 'Error processing SKU of GUID %1$s. %2$s', 'edi' ),
					$product_data['guid'],
					$e->getMessage()
				)
			);
		}
		$product->set_price( $product_data['price'] );
		$product->set_sale_price( $product_data['price'] );
		$product->set_regular_price( $product_data['price'] );

		$product->set_manage_stock( true );
		$product->set_stock_quantity( (float) $product_data['quantity'] );
		$product->set_stock_status();

		$product->add_meta_data( Utils::get_product_map_key(), $product_data['guid'], true );

		do_action_ref_array( 'edi_product_before_save', array( &$product, &$product_data ) );

		$product->save();

		EDI::log()->debug(
			sprintf(
			
				__( 'Product was created. GUID %1$s -> ID %2$d.', 'edi' ),
				$product_data['guid'],
				$product->get_id()
			)
		);

		return $product->get_id();
	}
}
