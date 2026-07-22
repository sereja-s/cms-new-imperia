<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;

use Exception;


class CatalogInterchange extends AbstractInterchange {
	
	protected function action_import(): void {
		if ( str_ends_with( $this->request->previous_filename, '.zip' ) ) {
			EDI::log()->debug(
			
				sprintf( __( 'Unpacking %s ...', 'edi' ), $this->request->previous_filename )
			);

			$this->action_import_unpack();
		} elseif (
			str_ends_with( $this->request->previous_filename, '.xml' )
			||
			(
				empty( $this->request->previous_filename )
				&&
				str_ends_with( $this->request->filename, '.xml' )
			)
		) {
			EDI::log()->debug(
			
				sprintf( __( 'Processing %s ...', 'edi' ), $this->request->filename )
			);

			$this->action_import_parse();
		} else {
			throw new Exception(
			
				sprintf( __( 'Unexpected file name: %s.', 'edi' ), $this->request->filename )
			);
		}
	}

	
	protected function action_import_unpack(): void {
		EDI::filesystem()->unzip_file( $this->request->previous_filename, '', 'debug' !== apply_filters( 'edi_logging_level', EDI_DEFAULT_LOGGING_LEVEL ) );

		EDI::log()->info( wp_json_encode( EDI::filesystem()->get_list_except_system_files() ) );

		$message = 'progress';

		EDI::log()->info( '🔙' . $message );

				exit( $message );
	}

	
	protected function action_import_parse(): void {
		if ( str_starts_with( $this->request->filename, 'import' ) ) {
			$phase = 'import';

			$parser_class_name = '\BytePerfect\EDI\Parsers\ImportXMLParser';
		} elseif ( str_starts_with( $this->request->filename, 'offers' ) ) {
			$phase = 'offers';

			$parser_class_name = '\BytePerfect\EDI\Parsers\OffersXMLParser';
		} else {
			EDI::log()->error(
			
				sprintf( __( 'Parser not found for file name: %s.', 'edi' ), $this->request->filename )
			);

						exit( 'success' );
		}

		$parser = new $parser_class_name( $this->request );
		if ( $parser->parse() ) {
			
			$this->send_finish_signal( $phase );

			$this->request->reset( Request::DO_NOT_CLEAR_REPOSITORY );

			$message = 'success';
		} else {
			
			$message = 'progress';
		}

		EDI::log()->info( '🔙' . $message );

				exit( $message );
	}

	
	protected function send_finish_signal( string $phase ): void {
		$post_args = array(
			'timeout'   => 0.01,
			'blocking'  => false,
			'sslverify' => false,
			'body'      => array(
				'action'      => 'edi_finish',
				'type'        => $this->request->type,
				'filename'    => $this->request->filename,
				'phase'       => $phase,
				'_ajax_nonce' => wp_create_nonce( 'edi_finish' ),
			),
		);

				wp_remote_post( admin_url( 'admin-ajax.php' ), $post_args );
	}
}
