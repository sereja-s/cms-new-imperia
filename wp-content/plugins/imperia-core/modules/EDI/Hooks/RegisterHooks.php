<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Hooks;

use Imperia\Modules\EDI\Hooks\Product\ProductGalleryHook;
use Imperia\Modules\EDI\Hooks\Product\ProductPrintNameParser;
use Imperia\Modules\EDI\Infrastructure\ParserRegistry;

/**
 * ==========================================================
 * RegisterHooks
 * ==========================================================
 *
 * Единственная задача данного класса —
 * зарегистрировать все хуки (Actions и Filters),
 * которыми модуль Imperia расширяет функциональность
 * плагина EDI.
 *
 * Здесь НЕ должно быть:
 *
 * • бизнес-логики;
 * • обработки XML;
 * • создания категорий;
 * • поиска товаров;
 * • работы с WooCommerce.
 *
 * Этот класс лишь сообщает WordPress:
 *
 * "Когда EDI вызовет такой-то фильтр —
 * передай управление нашему обработчику."
 *
 * Благодаря этому вся логика регистрации находится
 * в одном месте.
 */
final class RegisterHooks
{
	/**
	 * Реестр наших парсеров.
	 *
	 * Сейчас он умеет заменять только CategoriesParser,
	 * но позже здесь будут регистрироваться и другие:
	 *
	 * • ProductsParser
	 * • OffersParser
	 * • ImagesParser
	 * • OrdersParser
	 */
	private ParserRegistry $parserRegistry;

	/**
	 * При создании объекта сразу регистрируем
	 * все необходимые хуки WordPress.
	 *
	 * Поэтому Module достаточно выполнить:
	 *
	 * new RegisterHooks();
	 *
	 * и никаких дополнительных вызовов делать
	 * уже не потребуется.
	 */
	public function __construct()
	{
		$this->parserRegistry = new ParserRegistry();

		$this->register();

		/*
     * Использовать
     * "Полное наименование"
     * вместо стандартного названия.
     */
		(new ProductPrintNameParser())->register();

		/*
     * Исправление стандартной
     * работы галереи изображений.
     *
     * Первое изображение
     * становится главным
     * и больше не попадает
     * в галерею.
     */
		(new ProductGalleryHook())->register();
	}

	/**
	 * Регистрирует все фильтры и действия,
	 * используемые данным модулем.
	 *
	 * Пока зарегистрирован только один фильтр —
	 * замена стандартных XML-парсеров EDI
	 * на наши расширенные версии.
	 *
	 * В дальнейшем здесь могут появиться:
	 *
	 * add_action(...)
	 * add_filter(...)
	 * и другие точки расширения EDI.
	 */
	private function register(): void
	{
		add_filter(
			'edi_register_import_parsers',
			[$this, 'replaceParsers']
		);
	}

	/**
	 * Позволяет заменить стандартные парсеры EDI
	 * нашими собственными реализациями.
	 *
	 * Сам RegisterHooks НЕ знает,
	 * какие именно парсеры нужно заменить.
	 *
	 * Этим занимается ParserRegistry.
	 *
	 * @param array $parsers Список парсеров,
	 *                       зарегистрированных EDI.
	 *
	 * @return array Обновлённый список парсеров.
	 */
	public function replaceParsers(array $parsers): array
	{
		return $this->parserRegistry->replace($parsers);
	}
}
