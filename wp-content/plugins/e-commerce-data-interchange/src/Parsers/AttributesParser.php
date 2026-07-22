<?php declare( strict_types=1 );


namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Utils;
use Exception;


class AttributesParser {
	public const ATTRIBUTE_MAP_KEY = '_edi_1c_attribute_map';

	
	public function __construct() {
				add_action(
			'_/КоммерческаяИнформация/Классификатор/Свойства/Свойство',
			array( $this, 'process' )
		);
	}

	
	public function process( DataXML $xml_object ): void {
		$attribute_data = $this->parse_xml_object( $xml_object );

		try {
			if ( 'Справочник' !== $attribute_data['type'] ) {
				$this->process_individual_attribute( $attribute_data );
			} else {
				$taxonomy_name = $this->process_attribute( $attribute_data );
				$this->process_attribute_terms( $taxonomy_name, $attribute_data );
			}
		} catch ( Exception $e ) {
						EDI::log()->error( "Error processing attribute GUID {$attribute_data['guid']}." );
			EDI::log()->error( $e->getMessage() );
		}
	}

	
	protected function parse_xml_object( DataXML $xml_object ): array {
		$xml_data = $xml_object->GetArray();
		$xml_data = $xml_data['Свойство']['#'];

		$attribute_data = array(
			'guid'         => $xml_data['Ид'][0]['#'],
			'name'         => $xml_data['Наименование'][0]['#'],
			'type'         => $xml_data['ТипЗначений'][0]['#'] ?? '',
			'terms'        => array(),
		);

		if (
			'Справочник' === $attribute_data['type']
			&&
			$xml_data['ВариантыЗначений'][0]['#']
		) {
			foreach ( $xml_data['ВариантыЗначений'][0]['#']['Справочник'] as $variation ) {
				$guid  = $variation['#']['ИдЗначения'][0]['#'];
				$value = $variation['#']['Значение'][0]['#'];

				$attribute_data['terms'][ $guid ] = $value;
			}
		}

		return $attribute_data;
	}

	
	protected function process_attribute( array $attribute_data ): string {
		$attribute_id = Utils::get_guid_id_match(
			apply_filters( 'edi_attribute_map_key', self::ATTRIBUTE_MAP_KEY ), 			$attribute_data['guid'] 		);

		$args = array(
						'name' => $this->sanitize_attribute_name( $attribute_data['name'] ),
			'guid' => $attribute_data['guid'],
		);

		if (
			is_null( $attribute_id )
			||
			! taxonomy_exists( 'pa_' . $this->sanitize_attribute_slug( $attribute_data['name'] ) )
		) {
						$args['slug'] = $this->sanitize_attribute_slug( $attribute_data['name'] );

			$attribute_id = $this->create_attribute( $args );

			Utils::set_guid_id_match(
				apply_filters( 'edi_attribute_map_key', self::ATTRIBUTE_MAP_KEY ), 				$attribute_data['guid'], 				$attribute_id
			);
		} else {
			$args['slug'] = $this->get_attribute_slug( $attribute_id );

			$this->update_attribute( $attribute_id, $args );
		}

		$taxonomy_name = wc_attribute_taxonomy_name( $this->get_attribute_slug( $attribute_id ) );
		if ( ! taxonomy_exists( $taxonomy_name ) ) {
			$result = register_taxonomy(
				$taxonomy_name,
				array( 'product' ),
				array(
					'hierarchical' => false,
					'show_ui'      => false,
					'query_var'    => true,
					'rewrite'      => false,
				)
			);
			if ( is_wp_error( $result ) ) {
				throw new Exception(
					sprintf(
					
						__( 'Error register taxonomy: %s', 'edi' ),
												$result->get_error_message()
					)
				);
			}
		}

		return $taxonomy_name;
	}

	
	protected function sanitize_attribute_name( string $attribute_name ): string {
				return wc_clean( wp_unslash( $attribute_name ) );
	}

	
	protected function sanitize_attribute_slug( string $attribute_name ): string {
		$attribute_slug = $this->sanitize_attribute_name( $attribute_name );
		$attribute_slug = wc_sanitize_taxonomy_name( $attribute_slug );
		$attribute_slug = substr( Utils::transliterate( $attribute_slug ), 0, 27 );

		return $attribute_slug;
	}

	
	protected function create_attribute( array $args ): int {
		$attribute_id = wc_create_attribute( $args );

		if ( is_wp_error( $attribute_id ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error create attribute: %s', 'edi' ),
										$attribute_id->get_error_message()
				)
			);
		}

		EDI::log()->debug(
			sprintf(
			
				__( 'Attribute was created. GUID %1$s -> ID %2$d.', 'edi' ),
				$args['guid'],
				$attribute_id
			)
		);

				return $attribute_id;
	}

	
	protected function update_attribute( int $attribute_id, array $args ): void {
		$attribute_id = wc_update_attribute( $attribute_id, $args );

		if ( is_wp_error( $attribute_id ) ) {
			throw new Exception(
				sprintf(
				
					__( 'Error update attribute: %s', 'edi' ),
										$attribute_id->get_error_message()
				)
			);
		}

		EDI::log()->debug(
			sprintf(
			
				__( 'Attribute was updated. GUID %1$s -> ID %2$d.', 'edi' ),
				$args['guid'],
				$attribute_id
			)
		);
	}

	
	protected function get_attribute_slug( int $attribute_id ): string {
		global $wpdb;

				$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id = %d",
				$attribute_id
			)
		);

		if ( ! $result ) {
			throw new Exception(
				sprintf(
				
					__( 'Error get attribute slug by ID: %d.', 'edi' ),
					$attribute_id
				)
			);
		}

		return $result;
	}

	
	protected function process_attribute_terms( string $taxonomy_name, array $attribute_data ): void {
				foreach ( $attribute_data['terms'] as $guid => $term ) {
			$term_id = Utils::get_guid_id_match(
				apply_filters( 'edi_attribute_map_key', self::ATTRIBUTE_MAP_KEY ), 				$guid 			);

			if (
				is_null( $term_id )
				||
				! term_exists( $term, $taxonomy_name )
			) {
								$result = wp_insert_term( $term, $taxonomy_name );

				if ( is_wp_error( $result ) ) {
										EDI::log()->error( $result->get_error_message() . "[$term, $taxonomy_name]" );
					continue;
				}

				Utils::set_guid_id_match(
					apply_filters( 'edi_attribute_map_key', self::ATTRIBUTE_MAP_KEY ), 					$guid, 					$result['term_id'] 				);

				EDI::log()->debug(
					sprintf(
					
						__( 'Attribute term was created. GUID %1$s -> ID %2$d.', 'edi' ),
						$guid,
						$result['term_id']
					)
				);
			} else {
				$result = wp_update_term( $term_id, $taxonomy_name, array( 'name' => $term ) );

				if ( is_wp_error( $result ) ) {
										EDI::log()->error( $result->get_error_message() . "[$term, $taxonomy_name]" );
				} else {
					EDI::log()->debug(
						sprintf(
						
							__( 'Attribute term was updated. GUID %1$s -> ID %2$d.', 'edi' ),
							$guid,
							$term_id
						)
					);
				}
			}
		}
	}

	
	protected function process_individual_attribute( array $attribute_data ): void {
		$attribute_name = Utils::get_guid_id_match(
			apply_filters( 'edi_attribute_map_key', self::ATTRIBUTE_MAP_KEY ), 			$attribute_data['guid'] 		);

		if ( is_null( $attribute_name ) ) {
			Utils::set_guid_id_match(
				apply_filters( 'edi_attribute_map_key', self::ATTRIBUTE_MAP_KEY ), 				$attribute_data['guid'], 				$attribute_data["name"]
			);
		}
	}
}
