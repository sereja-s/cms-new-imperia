<?php declare( strict_types=1 );


namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Matchers\ProductMatcher;
use BytePerfect\EDI\Utils;
use Exception;
use WC_Data_Exception;
use WC_Product;


class ProductsParser {
	
	public function __construct() {
		add_action(
			'_/КоммерческаяИнформация/Каталог/Товары/Товар',
			array( $this, 'process' )
		);
	}

	
	public function process( DataXML $xml_object ): void {
		$product_data = $this->parse_xml_object( $xml_object );

		try {
			$product_id = ProductMatcher::locate( $product_data['guid'], $product_data['sku'], $product_data['name'] );

			if ( $product_id ) {
				$this->update_product( $product_id, $product_data );
			} else {
				$product_id = $this->create_product( $product_data );

				if ( $product_id > 0 ) {
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
					'Error processing GUID %s, product ID %d.',
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

		if ( isset( $xml_data['ЗначенияРеквизитов'] ) ) {
			foreach ( $xml_data['ЗначенияРеквизитов'][0]['#']['ЗначениеРеквизита'] as $property ) {
				$key   = $property['#']['Наименование'][0]['#'];
				$value = $property['#']['Значение'][0]['#'];

				switch ( $key ) {
					case 'Ширина':
						$width = (float) $value;
						break;
					case 'Длина':
						$length = (float) $value;
						break;
					case 'Высота':
						$height = (float) $value;
						break;
					case 'Вес':
						$weight = (float) $value;
						break;
				}
			}
		}

		$product_name = $xml_data['Наименование'][0]['#'] ?? '';
		$product_slug = $this->get_product_slug( $product_name );

				return apply_filters(
			'edi_parse_product_xml_object',
			array(
				'guid'        => $xml_data['Ид'][0]['#'] ?? '',
				'sku'         => $xml_data['Артикул'][0]['#'] ?? '',
				'name'        => $product_name,
				'slug'        => $product_slug,
				'description' => $xml_data['Описание'][0]['#'] ?? '',
				'width'       => $width ?? null,
				'length'      => $length ?? null,
				'height'      => $height ?? null,
				'weight'      => $weight ?? null,
				'categories'  => array(),
			),
			$xml_data
		);
	}

	
	protected function get_product_slug( string $product_name ): string {
		$product_slug = wp_unslash( $product_name );
		$product_slug = wc_clean( $product_slug );
		$product_slug = wc_sanitize_taxonomy_name( $product_slug );
		$product_slug = Utils::transliterate( $product_slug );

				return substr( $product_slug, 0, 200 );
	}

	
	protected function create_product( array $product_data ): int {
		$product = new WC_Product();

		$product_data = apply_filters( 'edi_parse_product_create_product_data', $product_data, $product );

		if ( ! empty( $product_data['name'] ) ) {
			$product->set_name( $product_data['name'] );
		}
		if ( ! empty( $product_data['slug'] ) ) {
			$product->set_slug( $product_data['slug'] );
		}
		if ( ! empty( $product_data['description'] ) ) {
			$product->set_description( $product_data['description'] );
		}
		try {
			if ( ! empty( $product_data['sku'] ) ) {
				$product->set_sku( $product_data['sku'] );
			}
		} catch ( WC_Data_Exception $e ) {
			EDI::log()->warning( "Error processing SKU of GUID {$product_data['guid']}. " . $e->getMessage() );
		}
		$product->set_stock_quantity( 0 );
		$product->set_stock_status( 'outofstock' );

		if ( ! empty( $product_data['weight'] ) ) {
			$product->set_weight( $product_data['weight'] );
		}
		if ( ! empty( $product_data['width'] ) ) {
			$product->set_width( $product_data['width'] );
		}
		if ( ! empty( $product_data['length'] ) ) {
			$product->set_length( $product_data['length'] );
		}
		if ( ! empty( $product_data['height'] ) ) {
			$product->set_height( $product_data['height'] );
		}

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

	
	protected function update_product( int $product_id, array $product_data ): void {
		$product = wc_get_product( $product_id );
		if ( ! $product instanceof WC_Product ) {
			throw new Exception( __( 'Is not a valid product.', 'edi' ) );
		}

		$product_data = apply_filters( 'edi_parse_product_update_product_data', $product_data, $product );

		if ( ! empty( $product_data['name'] ) ) {
			$product->set_name( $product_data['name'] );
		}
		if ( ! empty( $product_data['slug'] ) ) {
			$product->set_slug( $product_data['slug'] );
		}
		if ( ! empty( $product_data['description'] ) ) {
			$product->set_description( $product_data['description'] );
		}
		try {
			if ( ! empty( $product_data['sku'] ) ) {
				$product->set_sku( $product_data['sku'] );
			}
		} catch ( WC_Data_Exception $e ) {
			EDI::log()->warning( "Error processing SKU of product ID $product_id . " . $e->getMessage() );
		}
		$product->set_status( 'publish' );

		if ( ! empty( $product_data['weight'] ) ) {
			$product->set_weight( $product_data['weight'] );
		}
		if ( ! empty( $product_data['width'] ) ) {
			$product->set_width( $product_data['width'] );
		}
		if ( ! empty( $product_data['length'] ) ) {
			$product->set_length( $product_data['length'] );
		}
		if ( ! empty( $product_data['height'] ) ) {
			$product->set_height( $product_data['height'] );
		}

		do_action_ref_array( 'edi_product_before_save', array( &$product, &$product_data ) );

		$product->save();

		EDI::log()->debug(
			sprintf(
			
				__( 'Product was updated. GUID %1$s -> ID %2$d.', 'edi' ),
				$product_data['guid'],
				$product->get_id()
			)
		);
	}
}
