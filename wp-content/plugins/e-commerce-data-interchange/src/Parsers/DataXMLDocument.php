<?php declare( strict_types=1 );


namespace BytePerfect\EDI\Parsers;

class DataXMLDocument {
	var $version = '';
	var $encoding = '';

	
	var $children;
	var $root;

	public function __construct() {
	}

	function elementsByName( $tagname ) {
		$result = array();
		if ( is_array( $this->children ) ) {
			foreach ( $this->children as $node ) {
				$more = $node->elementsByName( $tagname );
				if ( is_array( $more ) ) {
					foreach ( $more as $mnode ) {
						array_push( $result, $mnode );
					}
				}
			}
		}

		return $result;
	}

	function encodeDataTypes( $name, $value ) {
		static $Xsd = array(
			"string"       => "string",
			"bool"         => "boolean",
			"boolean"      => "boolean",
			"int"          => "integer",
			"integer"      => "integer",
			"double"       => "double",
			"float"        => "float",
			"number"       => "float",
			"array"        => "anyType",
			"resource"     => "anyType",
			"mixed"        => "anyType",
			"unknown_type" => "anyType",
			"anyType"      => "anyType",
		);

		$node       = new DataXMLNode();
		$node->name = $name;

		if ( is_object( $value ) ) {
			$ovars = get_object_vars( $value );
			foreach ( $ovars as $pn => $pv ) {
				$decode = DataXMLDocument::encodeDataTypes( $pn, $pv );
				if ( $decode ) {
					array_push( $node->children, $decode );
				}
			}
		} elseif ( is_array( $value ) ) {
			foreach ( $value as $pn => $pv ) {
				$decode = DataXMLDocument::encodeDataTypes( $pn, $pv );
				if ( $decode ) {
					array_push( $node->children, $decode );
				}
			}
		} else {
			if ( isset( $Xsd[ gettype( $value ) ] ) ) {
				$node->content = $value;
			}
		}

		return $node;
	}

	
	function &__toString() {
		$ret = "<" . "?xml";
		if ( $this->version <> '' ) {
			$ret .= " version=\"" . $this->version . "\"";
		}
		if ( $this->encoding <> '' ) {
			$ret .= " encoding=\"" . $this->encoding . "\"";
		}
		$ret .= "?" . ">";

		if ( is_array( $this->children ) ) {
			foreach ( $this->children as $child ) {
				$ret .= $child->__toString();
			}
		}

		return $ret;
	}

	
	function &__toArray() {
		$arRetArray = array();

		if ( is_array( $this->children ) ) {
			foreach ( $this->children as $child ) {
				$arRetArray[ $child->name ] = $child->__toArray();
			}
		}

		return $arRetArray;
	}
}
