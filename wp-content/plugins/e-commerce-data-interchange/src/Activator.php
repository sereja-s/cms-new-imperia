<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;

use Exception;


class Activator {
	
	public static function run(): void {
		$request = new Request();
		$request->reset();
	}
}
