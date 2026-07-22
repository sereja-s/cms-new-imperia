<?php


declare( strict_types=1 );

namespace BytePerfect\EDI;

use Exception;


class Deactivator {
	
	public static function run(): void {
		$request = new Request();
		$request->reset();
	}
}
