<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;

use Exception;


class TestInterchange extends AbstractInterchange {

	
	protected function action_init(): void {
		$message = 'success';

		try {
			$script = EDI_PLUGIN_DIR . '/tests/' . $this->request->filename . '.php';
			if ( is_readable( $script ) ) {
				require_once $script;
			} else {
				$message = 'Script not found';
			}
		} catch ( Exception $e ) {
			$message = "❌ Ошибка: " . $e->getMessage() . "\n";
		}

				exit( $message );
	}

	protected function action_import(): void {
			}

	protected function action_import_unpack(): void {
			}

	protected function action_import_parse(): void {
			}
}
