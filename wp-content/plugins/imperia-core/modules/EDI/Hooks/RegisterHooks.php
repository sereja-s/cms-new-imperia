<?php

namespace Imperia\Modules\EDI\Hooks;

use Imperia\Modules\EDI\Infrastructure\ParserRegistry;

final class RegisterHooks
{

	public function register(): void
	{

		add_filter(

			'edi_register_import_parsers',

			[$this, 'replaceParsers']

		);
	}

	public function replaceParsers(array $parsers): array
	{

		return (new ParserRegistry())->replace($parsers);
	}
}
