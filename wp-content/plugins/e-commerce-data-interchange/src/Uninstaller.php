<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;

use Exception;


class Uninstaller {
	
	public static function run(): void {
		delete_option( 'edi' );
		delete_option( '_edi_mode' );
		delete_option( '_edi_type' );
		delete_option( '_edi_filename' );
		delete_option( '_edi_last_xml_entry' );
		delete_option( '_edi_1c_category_map' );
		delete_option( '_edi_1c_attribute_map' );
		delete_option( '_edi_1c_image_map' );
		delete_post_meta_by_key( '_edi_1c_guid' );
		delete_post_meta_by_key( '_edi_modified' );
	}
}
