<?php


declare( strict_types=1 );

namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Utils;
use Exception;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;


class OfferAttributesParser {
	
	public function __construct() {
				add_filter(
			'edi_parse_offer_xml_object',
			array( $this, 'parse_xml_object' ),
			10,
			2
		);

				add_action(
			'edi_offer_before_save',
			array( $this, 'update_product_attributes' ),
			10,
			2
		);
	}

	
	public function parse_xml_object( array $product_data, array $xml_data ): array {
		if ( ! $product_data['variation_guid'] ) {
			return $product_data;
		}

		$strategy_sequence = apply_filters(
			'edi_offer_attribute_search_sequence',
			array( 'properties' ),
			$product_data,
			$xml_data
		);

		foreach ( $strategy_sequence as $strategy ) {
			switch ( $strategy ) {
				case 'properties':
					if ( ! isset( $xml_data['ЗначенияСвойств'] ) ) {
						EDI::log()->debug( "No properties found for product {$product_data['guid']}" );

						continue 2;
					}

					$product_data['attributes'] = $this->get_attributes_from_properties( $xml_data['ЗначенияСвойств'] );

					break 2;
				case 'characteristics':
					if ( ! isset( $xml_data['ХарактеристикиТовара'] ) ) {
						EDI::log()->debug( "No characteristics found for product {$product_data['guid']}" );

						continue 2;
					}

					$product_data['attributes'] = $this->get_attributes_from_characteristics( $xml_data['ХарактеристикиТовара'] );

					break 2;
				case 'default':
										$default_attribute_name = apply_filters(
						'edi_default_attribute_name',
						'',
						$product_data,
						$xml_data
					);

										if ( empty( $default_attribute_name ) ) {
						EDI::log()->debug( "No default attribute name found for product {$product_data['guid']}" );
					} else {
						$product_data['attributes'] = $this->get_default_attributes( $product_data, $default_attribute_name );
					}

					break 2;
				default:
					EDI::log()->debug( "Unknown attribute search strategy: $strategy" );

					continue 2;
			}
		}

		if ( empty( $product_data['attributes'] ) ) {
			EDI::log()->error( "No attributes found for product {$product_data['guid']}" );
		}

		return $product_data;
	}

	
	public function update_product_attributes( $the_product, array $product_data ): void {
				if ( empty( $product_data['attributes'] ) ) {
			return;
		}

				if ( $the_product instanceof WC_Product_Variation ) {
			$this->update_variation_attributes( $the_product, $product_data['attributes'] );
		} elseif ( $the_product instanceof WC_Product_Variable ) {
			$this->update_variable_product_attributes( $the_product, $product_data['attributes'] );
		}
	}

	
	private function update_variable_product_attributes( WC_Product_Variable $parent_product, array $attributes ): void {
		$existing_attributes = $parent_product->get_attributes();

		foreach ( $attributes as $attribute_name => $attribute_value ) {
			$is_global_attribute = is_integer( $attribute_name );

			$attribute_id   = $is_global_attribute ? $attribute_name : 0;
			$attribute_name = $is_global_attribute ? wc_attribute_taxonomy_name_by_id( $attribute_name ) : $attribute_name;
			$attribute_slug = $is_global_attribute ? $attribute_name : sanitize_title( $attribute_name );

			if ( isset( $existing_attributes[ $attribute_slug ] ) ) {
								$attribute        = $existing_attributes[ $attribute_slug ];
				$current_values   = $attribute->get_options();
				$current_values[] = $attribute_value;

								$attribute->set_options( array_unique( array_filter( array_values( $current_values ) ) ) );
			} else {
								$attribute = new WC_Product_Attribute();
				$attribute->set_id( $attribute_id );
				$attribute->set_name( $attribute_name );
				$attribute->set_options( array( $attribute_value ) );
				$attribute->set_position( count( $attributes ) );
				$attribute->set_visible( true );
				$attribute->set_variation( true );
			}

			$existing_attributes[ $attribute_slug ] = $attribute;
		}

		$parent_product->set_attributes( array() ); 		$parent_product->set_attributes( $existing_attributes );

		EDI::log()->debug( "Variable product attributes updated for product {$parent_product->get_id()}." );
	}

	
	private function update_variation_attributes( WC_Product_Variation $variation, array $attributes ): void {
		foreach ( $attributes as $attribute_name => $attribute_value ) {
			$is_global_attribute = is_integer( $attribute_name );

			if ( $is_global_attribute ) {
				$attribute_slug = wc_attribute_taxonomy_name_by_id( $attribute_name );
								$attribute_term  = get_term( $attribute_value, $attribute_slug );
				$attribute_value = sanitize_title( $attribute_term->slug );
			} else {
				$attribute_slug  = sanitize_title( $attribute_name );
				$attribute_value = html_entity_decode( wc_clean( $attribute_value ), ENT_QUOTES, get_bloginfo( 'charset' ) ); 			}

			$attributes[ $attribute_slug ] = $attribute_value;

			if ( $attribute_slug !== $attribute_name ) {
				unset( $attributes[ $attribute_name ] );
			}
		}

				$variation->set_attributes( array() );
		$variation->set_attributes( $attributes );

		EDI::log()->debug( "Variation attributes updated for variation {$variation->get_id()}." );
	}

	
	private function get_default_attributes( array $product_data, string $default_attr_name ): array {
		if ( preg_match( '/\(([^)]+)\)(?!.*\([^)]+\))/', $product_data['name'], $matches ) ) {
			$attribute_value = trim( $matches[1] );
		} else {
			$attribute_value = substr( bin2hex( random_bytes( 10 ) ), 0, 20 );
		}

		return array( $default_attr_name => $attribute_value );
	}

	
	private function get_attributes_from_properties( array $properties ): array {
		$attributes = array();

		if ( ! isset( $properties[0], $properties[0]['#'], $properties[0]['#']['ЗначенияСвойства'] ) ) {
			return $attributes;
		}

		foreach ( $properties[0]['#']['ЗначенияСвойства'] as $property ) {
			$property_id    = $property['#']['Ид'][0]['#'];
			$property_value = $property['#']['Значение'][0]['#'];

			$attribute_id = Utils::get_guid_id_match(
							apply_filters( 'edi_attribute_map_key', AttributesParser::ATTRIBUTE_MAP_KEY ),
				$property_id
			);
			$attribute_id = is_null( $attribute_id ) ? $property_id : $attribute_id;

			$attribute_value = Utils::get_guid_id_match(
							apply_filters( 'edi_attribute_map_key', AttributesParser::ATTRIBUTE_MAP_KEY ),
				$property_value
			);
			$attribute_value = is_null( $attribute_value ) ? $property_value : $attribute_value;

			if ( $attribute_id && $attribute_value ) {
				$attributes[ $attribute_id ] = $attribute_value;
			}
		}

		return $attributes;
	}

	
	private function get_attributes_from_characteristics( array $characteristics ): array {
		$attributes = array();

		foreach ( $characteristics as $characteristic ) {
						$attribute_name  = $characteristic['#']['ХарактеристикаТовара'][0]['#']['Наименование'][0]['#'] ?? '';
			$attribute_value = $characteristic['#']['ХарактеристикаТовара'][0]['#']['Значение'][0]['#'] ?? '';

						$attribute_name = apply_filters( 'edi_offer_attribute_name', $attribute_name, $attribute_value, $characteristic );

			if ( $attribute_name && $attribute_value ) {
				$attributes[ $attribute_name ] = $attribute_value;
			}
		}

		return $attributes;
	}
}
