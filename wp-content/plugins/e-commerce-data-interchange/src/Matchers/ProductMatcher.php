<?php declare( strict_types=1 );


namespace BytePerfect\EDI\Matchers;


use BytePerfect\EDI\EDI;
use Exception;


class ProductMatcher {
	public const MATCH_SOURCE_GUID = 'guid';
	public const MATCH_SOURCE_SKU  = 'sku';
	public const MATCH_SOURCE_NAME = 'name';

	
	public static function locate( string $guid, string $sku = '', string $name = '', int $parent_product_id = 0 ): ?int {
		$timestamp = time();

				if ( empty( $guid ) ) {
			throw new Exception( 'Empty GUID. GUID: ' . $guid . ', SKU: ' . $sku . ', Name: ' . $name );
		}

		$product_id = self::get_product_id( $guid, $parent_product_id );
		if ( $product_id ) {
			return $product_id;
		}

		$product_id = self::get_product_id_by_sku( $sku, $parent_product_id );
		if ( $product_id && ! self::product_has_guid( $product_id ) ) {
			self::update_product_match_meta(
				$product_id,
				$guid,
				self::MATCH_SOURCE_SKU,
				$timestamp
			);

			return $product_id;
		}

		$product_id = self::get_product_id_by_name( $name, $parent_product_id );
		if ( $product_id && ! self::product_has_guid( $product_id ) ) {
			self::update_product_match_meta(
				$product_id,
				$guid,
				self::MATCH_SOURCE_NAME,
				$timestamp
			);

			return $product_id;
		}

		return null;
	}

	
	protected static function get_product_id( string $guid, int $parent_product_id = 0 ): ?int {
		if ( empty( $guid ) ) {
			return null;
		}

		$product_ids = get_posts(
			array(
				'numberposts' => 2, 								'meta_key'    => self::get_product_map_key(),
								'meta_value'  => $guid,
				'fields'      => 'ids',
				'post_type'   => array( 'product', 'product_variation' ),
				'post_status' => 'any',
				'post_parent' => $parent_product_id,
			)
		);

		if ( empty( $product_ids ) ) {
			return null;
		} elseif ( 1 === count( $product_ids ) ) {
						return (int) $product_ids[0];
		} else {
			throw new Exception( 'Multiple products found for GUID: ' . $guid );
		}
	}

	
	protected static function get_product_map_key(): string {
		$product_map_key = apply_filters( 'edi_product_map_key', '_edi_1c_guid' );

		return is_string( $product_map_key ) ? $product_map_key : '_edi_1c_guid';
	}

	
	protected static function product_has_guid( int $product_id ): bool {
		$existing_guid = get_post_meta( $product_id, self::get_product_map_key(), true );
		return ! empty( $existing_guid );
	}

	
	protected static function get_product_id_by_sku( string $sku, int $parent_product_id = 0 ): ?int {
		global $wpdb;

		if ( empty( $sku ) ) {
			return null;
		}

				$product_ids = $wpdb->get_col(
			$wpdb->prepare(
				"
				SELECT posts.ID
				FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->wc_product_meta_lookup} AS lookup ON posts.ID = lookup.product_id
				WHERE
				posts.post_type IN ( 'product', 'product_variation' )
				AND posts.post_status != 'trash'
				AND lookup.sku = %s
				AND posts.post_parent = %d
				",
				$sku,
				$parent_product_id
			)
		);

		if ( empty( $product_ids ) ) {
			return null;
		} elseif ( 1 === count( $product_ids ) ) {
			return (int) $product_ids[0];
		} else {
			throw new Exception( 'Multiple products found for SKU: ' . $sku );
		}
	}

	
	protected static function get_product_id_by_name( string $name, int $parent_product_id = 0 ): ?int {
		if ( empty( $name ) ) {
			return null;
		}

		$args = array(
			'numberposts' => 2, 			'post_type'   => array( 'product', 'product_variation' ),
			'post_status' => 'publish',
			'title'       => $name,
			'fields'      => 'ids',
			'post_parent' => $parent_product_id,
		);

		$product_ids = get_posts( $args );

		if ( empty( $product_ids ) ) {
			return null;
		} elseif ( 1 === count( $product_ids ) ) {
						return (int) $product_ids[0];
		} else {
			throw new Exception( 'Multiple products found for name: ' . $name );
		}
	}

	
	public static function update_product_match_meta( int $product_id, string $guid, string $source, ?int $synced_at = null ): void {
		if ( $product_id <= 0 ) {
			return;
		}

		$guid = trim( $guid );
		if ( '' === $guid ) {
			return;
		}

		$allowed_sources = array( 'guid', 'sku', 'name' );
		if ( ! in_array( $source, $allowed_sources, true ) ) {
			$source = 'guid';
		}

		$timestamp = $synced_at ?? time();

		update_post_meta( $product_id, self::get_product_map_key(), $guid );
		update_post_meta( $product_id, '_edi_guid_match_source', $source );
		update_post_meta( $product_id, '_edi_guid_match_synced_at', $timestamp );

		EDI::log()->debug(
			sprintf(
				
				__( 'Product was matched by %s. GUID %s, Product ID %d.', 'edi' ),
				$source,
				$guid,
				$product_id
			)
		);
	}
}
