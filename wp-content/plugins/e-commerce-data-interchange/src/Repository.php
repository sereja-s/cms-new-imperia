<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;

use Exception;


class Repository {
	
	public function clear(): void {
		$this->destroy();
		$this->create();
	}

	
	public function create(): void {
		EDI::filesystem()->mkdir();

		EDI::filesystem()->file_put_contents( '.htaccess', 'Deny from all' );
		EDI::filesystem()->file_put_contents( 'index.html', '' );
	}

	
	public function destroy(): void {
		EDI::filesystem()->rmdir();
	}
}
