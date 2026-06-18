<?php

namespace Imperia\Modules\Catalog;

use Imperia\Core\ModuleInterface;
use Imperia\Modules\Catalog\Hooks\AssetsLoader;
use Imperia\Modules\Catalog\Hooks\CategoryCacheInvalidator;
use Imperia\Modules\Catalog\Hooks\HeaderButton;

/**
 * ==========================================================
 * MODULE: CATALOG
 * ==========================================================
 *
 * Главный класс модуля каталога.
 *
 * Это точка входа модуля для ModuleManager.
 *
 * ModuleManager знает только одно:
 *
 * 1. Создать экземпляр модуля.
 * 2. Вызвать метод init().
 *
 * После этого управление полностью
 * передается самому модулю.
 *
 * ==========================================================
 *
 * ВАЖНО:
 *
 * Модуль НЕ должен:
 *
 * - выполнять запросы к БД;
 * - получать категории WooCommerce;
 * - строить дерево категорий;
 * - выводить HTML;
 * - выполнять тяжёлые вычисления.
 *
 * Задача модуля:
 *
 * - зарегистрировать необходимые hooks;
 * - связать между собой компоненты модуля.
 *
 * ==========================================================
 *
 * Текущая архитектура каталога:
 *
 * Module
 *     ↓
 * CategoryCacheInvalidator
 *     ↓
 * CategoryCache
 *     ↓
 * CategoryTree
 *
 * ==========================================================
 *
 * В будущем сюда могут быть добавлены:
 * 
 * - MenuRenderer
 * - AjaxLoader 
 *
 * При этом сам Module останется
 * только точкой регистрации.
 */
final class Module implements ModuleInterface
{
	/**
	 * ======================================================
	 * MODULE INITIALIZATION
	 * ======================================================
	 *
	 * Единственная публичная точка входа модуля.
	 *
	 * Вызывается ModuleManager во время
	 * загрузки системы.
	 *
	 * Здесь разрешается:
	 *
	 * - add_action()
	 * - add_filter()
	 * - создание объектов-регистраторов
	 *
	 * Здесь НЕ должно быть:
	 *
	 * - get_terms()
	 * - WP_Query
	 * - запросов к БД
	 * - бизнес-логики
	 */
	public function init(): void
	{
		/**
		 * Регистрируем механизм
		 * автоматической очистки кэша каталога.
		 *
		 * После регистрации WordPress будет
		 * отслеживать изменения категорий
		 * WooCommerce и автоматически
		 * сбрасывать transient каталога.
		 *
		 * События:
		 *
		 * - created_product_cat
		 * - edited_product_cat
		 * - delete_product_cat
		 */
		(new CategoryCacheInvalidator())
			->register();

		(new AssetsLoader())
			->register();

		/**
		 * Временный вывод меню.
		 */
		(new HeaderButton())
			->register();

		/**
		 * В будущем здесь будут регистрироваться
		 * остальные части каталога.
		 *
		 * Пример:
		 *		 
		 * (new MenuRenderer())->register();
		 *
		 * (new AjaxLoader())->register();
		 */
	}
}
