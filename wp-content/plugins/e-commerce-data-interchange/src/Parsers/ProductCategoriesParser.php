<?php declare( strict_types=1 );


namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Utils;
use Exception;
use WC_Product;


class ProductCategoriesParser {
	
	public function __construct() {
				add_filter(
			'edi_parse_product_xml_object',
			array( $this, 'parse_xml_object_groups' ),
			10,
			2
		);
		add_filter(
			'edi_parse_product_xml_object',
			array( $this, 'parse_xml_object_categories' ),
			10,
			2
		);

				add_action(
			'edi_product_before_save',
			array( $this, 'process' ),
			10,
			2
		);
	}

	
	public function parse_xml_object_groups( array $product_data, array $xml_data ): array {
		if ( ! isset( $xml_data['Группы'] ) ) {
			return $product_data;
		}

		foreach ( $xml_data['Группы'] as $category ) {
			$guid = $category['#']['Ид'][0]['#'];

			if ( ! $guid ) {
				continue;
			}

			try {
				$category_id = Utils::get_guid_id_match(
									apply_filters( 'edi_attribute_map_key', CategoriesParser::CATEGORY_MAP_KEY ),
					$guid
				);

				if ( $category_id ) {
					$product_data['categories'][] = $category_id;
				}
			} catch ( Exception $e ) {
				EDI::log()->error( $e->getMessage() );

				continue;
			}
		}

		return $product_data;
	}

	
	public function parse_xml_object_categories( array $product_data, array $xml_data ): array {
		if ( ! isset( $xml_data['Категория'] ) ) {
			return $product_data;
		}

		try {
			$guid = $xml_data['Категория'][0]['#'] ?? '';

			$category_id = Utils::get_guid_id_match(
							apply_filters( 'edi_attribute_map_key', CategoriesParser::CATEGORY_MAP_KEY ),
				$guid
			);

			if ( $category_id ) {
				$product_data['categories'][] = $category_id;
			}
		} catch ( Exception $e ) {
			EDI::log()->error( $e->getMessage() );
		}

		return $product_data;
	}

	
	public function process( WC_Product &$product, array &$product_data ): void {
		$product->set_category_ids( (array) $product_data['categories'] );
	}
}
