<?php


declare( strict_types=1 );

namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\Request;
use BytePerfect\EDI\Settings;
use Exception;


class OffersXMLParser extends XMLParser {
	
	public function __construct( Request $request ) {
		parent::__construct( $request );

		if ( Settings::get_import_products() ) {
			$this->parsers[] = __NAMESPACE__ . '\\OffersParser';
		}
		if ( Settings::get_import_attributes() ) {
			$this->parsers[] = __NAMESPACE__ . '\\AttributesParser';
			$this->parsers[] = __NAMESPACE__ . '\\OfferAttributesParser';
		}

		if ( Settings::get_import_images() ) {
			$this->parsers[] = __NAMESPACE__ . '\\ProductImagesParser';
		}

		$this->parsers = (array) apply_filters( 'edi_register_offers_parsers', $this->parsers );
		foreach ( $this->parsers as $parser ) {
			new $parser();
		}
	}
}
