<?php

namespace Imperia\Modules\EDI\Infrastructure;

use BytePerfect\EDI\Parsers\CategoriesParser as BaseCategoriesParser;
use Imperia\Modules\EDI\Parsers\CategoriesParser;

/**
 * ==========================================================
 * ParserRegistry
 * ==========================================================
 *
 * Отвечает за замену штатных парсеров EDI
 * на собственные реализации Imperia Core.
 *
 * Бизнес-логики здесь нет.
 * Только регистрация замен.
 *
 */
final class ParserRegistry
{
	/**
	 * Карта замен.
	 */
	private const MAP = [
		BaseCategoriesParser::class => CategoriesParser::class,
	];

	/**
	 * Заменяет штатные парсеры EDI
	 * на собственные.
	 */
	public function replace(array $parsers): array
	{
		foreach ($parsers as $index => $parser) {

			if (isset(self::MAP[$parser])) {

				$parsers[$index] = self::MAP[$parser];
			}
		}

		return $parsers;
	}
}
