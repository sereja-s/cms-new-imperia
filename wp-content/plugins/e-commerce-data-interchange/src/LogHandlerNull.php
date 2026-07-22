<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;

use WC_Log_Handler_File;


class LogHandlerNull extends WC_Log_Handler_File {
	
	public function handle( $timestamp, $level, $message, $context ) {
		return apply_filters( 'edi_log_handle', true, $this, $timestamp, $level, $message, $context );
	}

	
	public function format_entry_p( $timestamp, $level, $message, $context ) {
		return self::format_entry( $timestamp, $level, $message, $context );
	}

	
	public function add_p( $entry, $handle ) {
		return $this->add( $entry, $handle );
	}
}
