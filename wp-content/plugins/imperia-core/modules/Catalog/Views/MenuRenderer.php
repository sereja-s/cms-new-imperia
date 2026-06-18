<?php

namespace Imperia\Modules\Catalog\Views;

use Imperia\Modules\Catalog\Services\CategoryCache;

/**
 * ==========================================================
 * MENU RENDERER
 * ==========================================================
 *
 * Отвечает только за HTML.
 */
final class MenuRenderer
{
	private CategoryCache $cache;

	public function __construct()
	{
		$this->cache =
			new CategoryCache();
	}

	/**
	 * Вывод меню.
	 */
	public function render(): void
	{
		$tree =
			$this->cache->getTree();

		if (empty($tree)) {
			return;
		}

		echo '<div class="imperia-catalog">';

		echo '<button class="imperia-catalog__button">';
		echo 'Каталог товаров';
		echo '</button>';

		echo '<div class="imperia-catalog__dropdown">';

		$this->renderCategories($tree);

		$this->renderChildren(
			$tree[0]['children'] ?? []
		);

		echo '</div>';

		echo '</div>';
	}

	/**
	 * Левая колонка.
	 */
	private function renderCategories(
		array $tree
	): void {

		echo '<div class="imperia-catalog__left">';

		echo '<ul class="imperia-catalog__categories">';

		foreach ($tree as $category) {

			echo '<li class="imperia-catalog__category">';

			echo esc_html(
				$category['name']
			);

			echo '</li>';
		}

		echo '</ul>';

		echo '</div>';
	}

	/**
	 * Правая колонка.
	 */
	private function renderChildren(
		array $children
	): void {

		echo '<div class="imperia-catalog__right">';

		echo '<ul class="imperia-catalog__children">';

		foreach ($children as $child) {

			echo '<li>';

			echo esc_html(
				$child['name']
			);

			echo '</li>';
		}

		echo '</ul>';

		echo '</div>';
	}
}
