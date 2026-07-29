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
		/* imperia_log(
			'CategoryCacheInvalidator registered'
		); */

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

		/**
		 * ======================================================
		 * ИЗМЕНЕНИЕ ПРИВЯЗОК КАТЕГОРИЙ У ТОВАРА
		 * ======================================================
		 *
		 * WooCommerce вызывает этот hook каждый раз,
		 * когда товару изменяют категории.
		 *
		 * Не важно:
		 *
		 * • обмен из 1С;
		 * • ручное редактирование;
		 * • массовое редактирование;
		 * • другой плагин.
		 *
		 * Нас интересует только один случай:
		 *
		 * последний товар покинул категорию
		 * "Без категории".
		 */
		add_action(
			'set_object_terms',
			[$this, 'checkDefaultCategory'],
			10,
			6
		);

		/**
		 * ======================================================
		 * ИЗМЕНЕНИЕ КАТАЛОГА ВНЕШНИМИ СИСТЕМАМИ
		 * ======================================================
		 *
		 * На это событие могут подписываться любые
		 * внешние источники изменений каталога.
		 *
		 * Например:
		 *
		 * • обмен с 1С (EDI);
		 * • собственный импортёр;
		 * • REST API;
		 * • будущие интеграции.
		 *
		 * Сам Imperia Core ничего не знает
		 * о плагине EDI.
		 *
		 * Он просто реагирует на событие:
		 *
		 *     imperia_catalog_changed
		 *
		 * После получения события
		 * очищается кэш дерева категорий.
		 * ======================================================
		 */
		/* add_action(
			'imperia_catalog_changed',
			[$this, 'clearCache']
		); */
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
		imperia_log(
			'CategoryCacheInvalidator fired'
		);

		$cache =
			new CategoryCache();

		$cache->clear();

		imperia_log(
			'Catalog cache cleared'
		);
	}

	/**
	 * ======================================================
	 * CHECK DEFAULT CATEGORY
	 * ======================================================
	 *
	 * Проверяет только один случай:
	 *
	 * системная категория WooCommerce
	 * осталась без товаров.
	 *
	 * Если это произошло —
	 * очищаем кэш каталога.
	 *
	 * Благодаря этому:
	 *
	 * CategoryTree построится заново,
	 * а пустая категория
	 * автоматически исчезнет
	 * из меню каталога.
	 *
	 * Во всех остальных случаях
	 * ничего не делаем.
	 */
	public function checkDefaultCategory(

		$objectId,

		$terms,

		$ttIds,

		string $taxonomy,

		bool $append,

		array $oldTtIds

	): void {

		/**
		 * Нас интересуют
		 * только категории товаров.
		 */
		if ($taxonomy !== 'product_cat') {
			return;
		}

		/**
		 * Нас интересуют только товары WooCommerce.
		 *
		 * Hook set_object_terms вызывается
		 * для любых объектов WordPress:
		 *
		 * - записи;
		 * - страницы;
		 * - товары;
		 * - медиафайлы;
		 * - любые CPT.
		 *
		 * Поэтому сразу отбрасываем всё,
		 * кроме товаров.
		 */
		if (get_post_type($objectId) !== 'product') {
			return;
		}

		/**
		 * Получаем ID категории,
		 * назначенной WooCommerce
		 * по умолчанию.
		 */
		$defaultCategoryId =
			(int) get_option(
				'default_product_cat'
			);

		/**
		 * Получаем taxonomy_id
		 * этой категории.
		 *
		 * Именно taxonomy_id
		 * хранится в массиве
		 * $oldTtIds.
		 */
		$term = get_term(
			$defaultCategoryId,
			'product_cat'
		);

		if (!$term) {
			return;
		}

		$defaultTaxonomyId =
			(int) $term->term_taxonomy_id;

		/**
		 * Если раньше товар
		 * вообще не находился
		 * в категории
		 * "Без категории",
		 * значит нас это
		 * изменение не интересует.
		 */
		if (
			!in_array(
				$defaultTaxonomyId,
				$oldTtIds,
				true
			)
		) {
			return;
		}

		/**
		 * Перечитываем категорию
		 * уже после изменения.
		 */
		clean_term_cache(
			$defaultCategoryId,
			'product_cat'
		);

		$defaultCategory =
			get_term(
				$defaultCategoryId,
				'product_cat'
			);

		if (!$defaultCategory) {
			return;
		}

		/**
		 * Если товаров
		 * больше нет —
		 * очищаем кэш каталога.
		 */
		if (
			(int) $defaultCategory->count === 0
		) {

			imperia_log(
				'Default category became empty. Clearing catalog cache.'
			);

			$this->clearCache();
		}
	}
}
