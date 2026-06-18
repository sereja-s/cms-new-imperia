<?php

namespace Imperia\Modules\Catalog\Services;

/**
 * ==========================================================
 * CATEGORY TREE SERVICE
 * ==========================================================
 *
 * Отвечает за получение дерева категорий WooCommerce.
 *
 * Выполняет:
 *
 * - один запрос к БД;
 * - построение дерева в памяти;
 * - возврат готовой структуры.
 *
 * Не отвечает за:
 *
 * - HTML;
 * - CSS;
 * - JavaScript;
 * - AJAX;
 * - кэширование.
 */
final class CategoryTree
{
	/**
	 * Карта терминов.
	 *
	 * Формат:
	 *
	 * [
	 *     17 => WP_Term,
	 *     18 => WP_Term,
	 * ]
	 *
	 * Нужна чтобы быстро получать
	 * информацию о категории по ID.
	 *
	 * @var array<int,\WP_Term>
	 */
	private array $termsMap = [];


	/**
	 * Индекс родителей.
	 *
	 * Формат:
	 *
	 * [
	 *     0 => [17, 18],
	 *     17 => [25, 26],
	 * ]
	 *
	 * Где:
	 *
	 * ключ = parent_id
	 * значение = список дочерних id
	 *
	 * @var array<int,array<int>>
	 */
	private array $childrenIndex = [];


	/**
	 * Получить дерево категорий.
	 */
	public function getTree(): array
	{
		$terms = get_terms(
			[
				'taxonomy'   => 'product_cat',
				'hide_empty' => true,
				'number'     => 0,
				'orderby'    => 'name',
				'order'      => 'ASC',
			]
		);

		if (
			empty($terms)
			|| is_wp_error($terms)
		) {
			return [];
		}

		$this->buildIndexes($terms);

		return $this->buildBranch(0);
	}


	/**
	 * Построение внутренних индексов.
	 *
	 * Создаём:
	 *
	 * 1. Карту категорий
	 * 2. Индекс родителей
	 */
	private function buildIndexes(array $terms): void
	{
		$this->termsMap = [];

		$this->childrenIndex = [];

		foreach ($terms as $term) {

			/**
			 * Карта категорий.
			 */
			$this->termsMap[$term->term_id] = $term;

			/**
			 * Если массива для родителя
			 * ещё нет — создаём.
			 */
			if (
				! isset(
					$this->childrenIndex[$term->parent]
				)
			) {
				$this->childrenIndex[$term->parent] = [];
			}

			/**
			 * Добавляем ребёнка родителю.
			 */
			$this->childrenIndex[$term->parent][] =
				$term->term_id;
		}
	}


	/**
	 * Построение ветки дерева.
	 *
	 * Работает только с массивами PHP.
	 *
	 * Запросов к БД больше нет.
	 */
	private function buildBranch(
		int $parentId
	): array {

		$branch = [];

		/**
		 * Если детей нет.
		 */
		if (
			! isset(
				$this->childrenIndex[$parentId]
			)
		) {
			return [];
		}

		foreach (
			$this->childrenIndex[$parentId]
			as $childId
		) {

			$term =
				$this->termsMap[$childId];

			$branch[] = [

				'id' => $term->term_id,

				'name' => $term->name,

				'slug' => $term->slug,

				/**
				 * Рекурсия уже работает
				 * только по массивам.
				 */
				'children' =>
				$this->buildBranch(
					$term->term_id
				),
			];
		}

		return $branch;
	}
}
