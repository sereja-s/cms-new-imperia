<?php


declare( strict_types=1 );

namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Utils;
use Exception;
use WC_Product;


class ProductImagesParser {
	public const IMAGE_MAP_KEY = '_edi_1c_image_map';

	
	public function __construct() {
				add_filter(
			'edi_parse_product_xml_object',
			array( $this, 'parse_xml_object' ),
			10,
			2
		);

				add_filter(
			'edi_parse_offer_xml_object',
			array( $this, 'parse_xml_object' ),
			10,
			2
		);

				add_action(
			'edi_product_before_save',
			array( $this, 'process' ),
			10,
			2
		);

				add_action(
			'edi_offer_before_save',
			array( $this, 'process' ),
			10,
			2
		);
	}

	
	public function parse_xml_object( array $product_data, array $xml_data ): array {
		$product_data['images'] = array();

		if ( ! isset( $xml_data['Картинка'] ) ) {
			return $product_data;
		}

		foreach ( $xml_data['Картинка'] as $image ) {
			$image = $image['#'];
			$guid  = md5( $image );

			try {
				$image_id = Utils::get_guid_id_match(
									apply_filters( 'edi_image_map_key', self::IMAGE_MAP_KEY ),
					$guid
				);

				if ( is_null( $image_id ) ) {
					$image_id = $this->upload_image( $image, $guid, $product_data['name'] );
				}

				if ( $image_id ) {
					$product_data['images'][] = $image_id;
				}
			} catch ( Exception $e ) {
				EDI::log()->error( $e->getMessage() );
			}
		}

		return $product_data;
	}

	
	public function process( WC_Product &$product, array &$product_data ): void {
		if ( $product_data['images'] ) {
						$remove_first_image = apply_filters( 'edi_remove_first_image_from_gallery', false, $product, $product_data );

			if ( $remove_first_image ) {
				$product->set_image_id( (int) array_shift( $product_data['images'] ) );
			} else {
				$product->set_image_id( (int) $product_data['images'][0] );
			}

			$product->set_gallery_image_ids( (array) $product_data['images'] );
		}
	}

	
	protected function upload_image( string $image, string $guid, string $title ): int {
		$src = EDI::filesystem()->normalize_path( $image );

		if ( ! function_exists( 'wp_read_image_metadata' ) ) {
			include_once ABSPATH . 'wp-admin/includes/image.php';
		}
		if ( ! function_exists( 'media_handle_sideload' ) ) {
			include_once ABSPATH . 'wp-admin/includes/media.php';
		}

		$attachment_id = media_handle_sideload(
			array(
				'tmp_name' => $src,
				'name'     => basename( $src ),
			),
			0,
			$title
		);

		if ( is_wp_error( $attachment_id ) ) {
			throw new Exception(
			
				sprintf( __( 'Error upload image: %s', 'edi' ), $attachment_id->get_error_message() )
			);
		}

		Utils::set_guid_id_match(
					apply_filters( 'edi_image_map_key', self::IMAGE_MAP_KEY ),
			$guid,
			$attachment_id
		);

		return $attachment_id;
	}
}
