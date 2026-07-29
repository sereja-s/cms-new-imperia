<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Parsers;

use BytePerfect\EDI\Parsers\CategoriesParser as EdiCategoriesParser;
use Imperia\Modules\EDI\Support\LoggerTrait;

/**
 * ==========================================================
 * CategoriesParser
 * ==========================================================
 *
 * Собственный обработчик категорий Imperia.
 *
 * Наследуется от штатного парсера EDI.
 *
 * Сам EDI остаётся нетронутым.
 *
 * Здесь будет постепенно размещаться
 * вся логика обработки категорий,
 * которая раньше изменялась прямо
 * внутри плагина EDI.
 *
 */
final class CategoriesParser extends EdiCategoriesParser
{
	use LoggerTrait;

	/**
	 * Пока класс ничего не переопределяет.
	 *
	 * Это сделано специально.
	 *
	 * На первом этапе мы лишь заменяем
	 * стандартный парсер нашим собственным.
	 *
	 * Далее постепенно будем переносить
	 * сюда отдельные методы:
	 *
	 * • process_category()
	 * • create_category()
	 * • update_category()
	 * • find_category_by_name()
	 *
	 * После переноса каждого метода
	 * оригинальный EDI можно будет вернуть
	 * к исходному состоянию.
	 */
}
