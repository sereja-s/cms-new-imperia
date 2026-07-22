<?php


declare( strict_types=1 );

namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\Request;
use BytePerfect\EDI\Settings;
use Exception;


class OrdersXMLParser extends XMLParser {
	
	public function __construct( Request $request ) {
		parent::__construct( $request );

		if ( Settings::get_import_orders() ) {
			$this->parsers[] = __NAMESPACE__ . '\\SaleProductsParser';
			$this->parsers[] = __NAMESPACE__ . '\\DocumentsParser';
		}

		$this->parsers = (array) apply_filters( 'edi_register_offers_parsers', $this->parsers );
		foreach ( $this->parsers as $parser ) {
			new $parser();
		}
	}
}
