<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;

use Exception;


abstract class AbstractInterchange {
	
	protected Request $request;

	
	public function __construct( Request $request ) {
		$this->request = $request;
	}

	
	public function run(): void {
		EDI::log()->notice(
			__( 'Running interchange...', 'edi' ) . PHP_EOL . $this->request
		);

		EDI::log()->info( 'REQUEST ' . json_encode( $_REQUEST ) );

		$callback = array( $this, 'action_' . $this->request->mode );
		if ( is_callable( $callback ) ) {
			call_user_func( $callback );
		} else {
			throw new Exception(
			
				sprintf( __( 'Mode is not supported: %s', 'edi' ), $this->request->mode )
			);
		}
	}

	
	protected function action_checkauth(): void {
		$message = "success\nedi\n" . time();

		EDI::log()->info( '🔙' . $message );

				exit( $message );
	}

	
	protected function action_init(): void {
		$this->request->reset( 'debug' !== apply_filters( 'edi_logging_level', EDI_DEFAULT_LOGGING_LEVEL ) );

				$message = sprintf(
			"zip=yes\nfile_limit=%d",
			Utils::get_file_limit()
		);

		EDI::log()->info( '🔙' . $message );

				exit( $message );
	}

	
	protected function action_file(): void {
		EDI::filesystem()->receive_file( $this->request->filename );

		$message = 'success';

		EDI::log()->info( '🔙' . $message );

				exit( $message );
	}

	
	abstract protected function action_import(): void;

	
	abstract protected function action_import_unpack(): void;

	
	abstract protected function action_import_parse(): void;
}
