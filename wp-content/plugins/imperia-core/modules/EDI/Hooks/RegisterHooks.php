<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Hooks;

use Imperia\Modules\EDI\Hooks\Product\DisablePendingStatus;
use Imperia\Modules\EDI\Hooks\Product\ProductAttributesKeyCompatibility;
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
 * которыми модуль Imperia расширяет
 * функциональность плагина EDI.
 */
final class RegisterHooks
{
	/**
	 * Реестр наших парсеров.
	 */
	private ParserRegistry $parserRegistry;

	/**
	 * При создании объекта регистрируем
	 * все необходимые расширения EDI.
	 */
	public function __construct()
	{
		$this->parserRegistry = new ParserRegistry();

		$this->register();

		/*
		 * Использовать "Полное наименование"
		 * вместо стандартного названия.
		 */
		(new ProductPrintNameParser())->register();

		/*
		 * Исправление стандартной работы галереи.
		 *
		 * Первое изображение становится главным
		 * и больше не попадает в галерею.
		 */
		(new ProductGalleryHook())->register();

		/*
		 * Отключает штатный перевод опубликованных
		 * товаров в статус "pending" перед обменом.
		 */
		(new DisablePendingStatus())->register();

		/*
		 * Приводим существующие ключи атрибутов
		 * к ключам, которые использует WooCommerce.
		 *
		 * Ошибка этого механизма не должна
		 * останавливать EDI-обмен.
		 */
		//$this->registerProductAttributesKeyCompatibility();

		/*
		 * После завершения тестирования эту строку
		 * при необходимости можно удалить.
		 */
		/* imperia_log(
			'ParserRegistry, ProductPrintNameParser, ' .
				'ProductGalleryHook, DisablePendingStatus, ' .
				'registerProductAttributesKeyCompatibility'
		); */
	}

	/**
	 * Регистрирует фильтры и действия EDI,
	 * используемые Imperia Core.
	 */
	private function register(): void
	{
		add_filter(
			'edi_register_import_parsers',
			[$this, 'replaceParsers']
		);
	}

	/**
	 * Заменяет штатные парсеры EDI
	 * собственными реализациями Imperia.
	 *
	 * @param array $parsers
	 *
	 * @return array
	 */
	public function replaceParsers(array $parsers): array
	{
		return $this->parserRegistry->replace($parsers);
	}

	/**
	 * Безопасная регистрация исправления ключей
	 * индивидуальных атрибутов.
	 *
	 * Ошибка нашего расширения не должна останавливать
	 * основной EDI-обмен.
	 *
	 * @return void
	 */
	private function registerProductAttributesKeyCompatibility(): void
	{
		try {
			(new ProductAttributesKeyCompatibility())->register();
		} catch (\Throwable $e) {
			try {
				imperia_log(
					'[ProductAttributesKeyCompatibility] ERROR' .
						' | stage=registration' .
						' | message=' . $e->getMessage() .
						' | file=' . $e->getFile() .
						' | line=' . $e->getLine()
				);
			} catch (\Throwable $ignored) {
				// Logging must never break the EDI exchange.
			}
		}
	}
}
