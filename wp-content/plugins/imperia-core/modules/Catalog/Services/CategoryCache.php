<?php

namespace Imperia\Modules\Catalog\Services;

/**
 * ==========================================================
 * CATEGORY CACHE SERVICE
 * ==========================================================
 *
 * Сервис кэширования дерева каталога.
 *
 * Ответственность:
 *
 * - получить дерево из transient;
 * - создать дерево при отсутствии кэша;
 * - сохранить результат;
 * - очистить кэш.
 *
 * Не отвечает за:
 *
 * - HTML;
 * - вывод;
 * - JS;
 * - hooks.
 *
 * ==========================================================
 */
final class CategoryCache
{

	/**
	 * Имя transient.
	 */
	private const CACHE_KEY =
	'imperia_catalog_tree';



	/**
	 * Время жизни кэша.
	 *
	 * 12 часов.
	 *
	 * Даже если hook очистки
	 * не сработает,
	 * данные обновятся автоматически.
	 */
	private const CACHE_TTL =
	12 * HOUR_IN_SECONDS;



	/**
	 * Сервис построения дерева.
	 */
	private CategoryTree $treeService;



	/**
	 * Создание зависимости.
	 */
	public function __construct()
	{
		$this->treeService =
			new CategoryTree();
	}



	/**
	 * Получение дерева.
	 */
	public function getTree(): array
	{
		// тест: принудительно чистит кеш дерева категорий
		/* delete_transient(
			self::CACHE_KEY
		); */

		/**
		 * Проверяем transient.
		 */
		$cachedTree =
			get_transient(
				self::CACHE_KEY
			);



		/**
		 * Если кэш найден
		 * и является массивом.
		 */
		if (
			is_array($cachedTree)
		) {


			if (
				defined('WP_DEBUG')
				&& WP_DEBUG
			) {

				imperia_log(
					'Catalog cache hit'
				);
			}


			return $cachedTree;
		}



		/**
		 * Кэш отсутствует.
		 */
		if (
			defined('WP_DEBUG')
			&& WP_DEBUG
		) {

			imperia_log(
				'Catalog cache miss'
			);
		}



		/**
		 * Строим дерево.
		 */
		$tree =
			$this->treeService
			->getTree();


		// тест (вывод массива первой категории)
		/* if (
			defined('WP_DEBUG')
			&& WP_DEBUG
		) {
			imperia_log(
				print_r(
					$tree[0] ?? [],
					true
				)
			);
		} */



		/**
		 * Сохраняем только массив.
		 *
		 * Если по какой-то причине
		 * сервис вернул что-то другое,
		 * кэшировать это нельзя.
		 */
		if (
			is_array($tree)
		) {


			set_transient(
				self::CACHE_KEY,
				$tree,
				self::CACHE_TTL
			);
		}



		return $tree;
	}



	/**
	 * Очистка transient.
	 *
	 * Вызывается:
	 *
	 * CategoryCacheInvalidator
	 */
	public function clear(): void
	{

		delete_transient(
			self::CACHE_KEY
		);
	}
}
