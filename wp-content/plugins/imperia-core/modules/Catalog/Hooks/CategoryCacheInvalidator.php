<?php

namespace Imperia\Modules\Catalog\Hooks;

use Imperia\Modules\Catalog\Services\CategoryCache;

/**
 * ==========================================================
 * CATEGORY CACHE INVALIDATOR
 * ==========================================================
 *
 * Назначение:
 *
 * Автоматический сброс кэша каталога
 * при изменении категорий WooCommerce.
 *
 * Ответственность класса:
 *
 * - регистрация WordPress hooks;
 * - очистка transient каталога.
 *
 * Не отвечает за:
 *
 * - построение дерева;
 * - работу с категориями;
 * - HTML;
 * - меню;
 * - AJAX.
 */
final class CategoryCacheInvalidator
{
	/**
	 * ======================================================
	 * REGISTER
	 * ======================================================
	 *
	 * Регистрируем события WooCommerce.
	 *
	 * Эти события вызываются когда:
	 *
	 * - создана категория;
	 * - изменена категория;
	 * - удалена категория.
	 */
	public function register(): void
	{
		add_action(
			'created_product_cat',
			[$this, 'clearCache']
		);

		add_action(
			'edited_product_cat',
			[$this, 'clearCache']
		);

		add_action(
			'delete_product_cat',
			[$this, 'clearCache']
		);
	}


	/**
	 * ======================================================
	 * CLEAR CACHE
	 * ======================================================
	 *
	 * Очистка кэша каталога.
	 *
	 * После удаления transient
	 * следующий запрос к каталогу:
	 *
	 * CategoryCache
	 *      ↓
	 * cache miss
	 *      ↓
	 * CategoryTree
	 *      ↓
	 * новое дерево
	 *      ↓
	 * новый transient
	 */
	public function clearCache(): void
	{
		$cache =
			new CategoryCache();

		$cache->clear();

		if (
			defined('WP_DEBUG')
			&& WP_DEBUG
		) {
			imperia_log(
				'Catalog cache cleared'
			);
		}
	}
}
