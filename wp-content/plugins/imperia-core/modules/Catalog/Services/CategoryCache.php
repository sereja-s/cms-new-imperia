<?php

namespace Imperia\Modules\Catalog\Services;

/**
 * ==========================================================
 * CATEGORY CACHE SERVICE
 * ==========================================================
 *
 * Назначение:
 *
 * Хранение дерева категорий WooCommerce
 * в transient-кэше WordPress.
 *
 * Ответственность класса:
 *
 * - получение дерева из кэша;
 * - построение дерева при отсутствии кэша;
 * - сохранение дерева в кэш;
 * - очистка кэша.
 *
 * Не отвечает за:
 *
 * - регистрацию hooks;
 * - HTML-разметку;
 * - AJAX;
 * - вывод меню;
 * - работу с темой.
 *
 * Эти задачи должны выполняться
 * отдельными классами.
 */
final class CategoryCache
{
	/**
	 * ======================================================
	 * CACHE KEY
	 * ======================================================
	 *
	 * Имя transient.
	 *
	 * В базе WordPress будет создана запись:
	 *
	 * _transient_imperia_catalog_tree
	 *
	 * и служебная запись:
	 *
	 * _transient_timeout_imperia_catalog_tree
	 */
	private const CACHE_KEY =
	'imperia_catalog_tree';


	/**
	 * ======================================================
	 * CACHE TTL
	 * ======================================================
	 *
	 * Время жизни кэша.
	 *
	 * Даже если механизм сброса по hooks
	 * не сработает по какой-либо причине,
	 * через указанное время WordPress
	 * автоматически удалит transient.
	 */
	private const CACHE_TTL =
	12 * HOUR_IN_SECONDS;


	/**
	 * ======================================================
	 * TREE SERVICE
	 * ======================================================
	 *
	 * Сервис построения дерева категорий.
	 */
	private CategoryTree $treeService;


	/**
	 * ======================================================
	 * CONSTRUCTOR
	 * ======================================================
	 *
	 * Подготавливаем сервис дерева.
	 */
	public function __construct()
	{
		$this->treeService =
			new CategoryTree();
	}


	/**
	 * ======================================================
	 * GET CACHE KEY
	 * ======================================================
	 *
	 * Возвращает имя transient.
	 *
	 * Может использоваться:
	 *
	 * - в отладке;
	 * - в административных инструментах;
	 * - в тестах.
	 */
	public static function getCacheKey(): string
	{
		return self::CACHE_KEY;
	}


	/**
	 * ======================================================
	 * GET TREE
	 * ======================================================
	 *
	 * Получить дерево категорий.
	 *
	 * Алгоритм:
	 *
	 * 1. Проверяем transient.
	 * 2. Если найден:
	 *      возвращаем кэш.
	 * 3. Если отсутствует:
	 *      строим дерево.
	 * 4. Сохраняем в transient.
	 * 5. Возвращаем результат.
	 *
	 * @return array
	 */
	public function getTree(): array
	{
		$cachedTree =
			get_transient(
				self::CACHE_KEY
			);

		/**
		 * Кэш найден.
		 */
		if (is_array($cachedTree)) {

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
		 * Строим дерево категорий.
		 */
		$tree =
			$this->treeService
			->getTree();

		/**
		 * Сохраняем в transient.
		 */
		set_transient(
			self::CACHE_KEY,
			$tree,
			self::CACHE_TTL
		);

		return $tree;
	}


	/**
	 * ======================================================
	 * CLEAR CACHE
	 * ======================================================
	 *
	 * Полностью удаляет transient.
	 *
	 * Следующий вызов getTree()
	 * автоматически построит новое дерево.
	 */
	public function clear(): void
	{
		delete_transient(
			self::CACHE_KEY
		);
	}
}
