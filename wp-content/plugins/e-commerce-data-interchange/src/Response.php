<?php declare( strict_types=1 );


namespace BytePerfect\EDI;


class Response {
	
	protected string $type;

	
	protected string $message;

	
	public function __construct( string $type = '', string $message = '' ) {
		$this->set_type( $type );
		$this->set_message( $message );
	}

	
	public function send( bool $exit = true ): void {
		$message = $this->message;
		if ( $this->type ) {
			$message = "$this->type\n$message";
		}

		if ( 'failure' === $this->type || 'debug' === apply_filters( 'edi_logging_level', EDI_DEFAULT_LOGGING_LEVEL ) ) {
			EDI::log()->notice( '🔙' . $message );
		}

		echo wp_kses_post( $message );

		if ( $exit ) {
			exit();
		}
	}

	
	protected function set_type( string $type ): void {
		$this->type = $type;

		if ( ! in_array( $this->type, array( 'success', 'failure', 'progress' ), true ) ) {
			$this->type = '';
		}
	}

	
	protected function set_message( string $message ): void {
		$this->message = $message;
	}
}
