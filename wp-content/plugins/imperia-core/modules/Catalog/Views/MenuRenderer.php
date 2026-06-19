<?php

namespace Imperia\Modules\Catalog\Views;

use Imperia\Modules\Catalog\Services\CategoryCache;


/**
 * ==========================================================
 * MENU RENDERER
 * ==========================================================
 *
 * Отвечает за HTML-разметку каталога.
 *
 * Получает готовое дерево категорий
 * через CategoryCache.
 *
 * Отвечает только за:
 *
 * - HTML;
 * - передачу данных JavaScript.
 *
 * Не отвечает за:
 *
 * - построение дерева;
 * - работу с WooCommerce;
 * - кэширование.
 *
 * ==========================================================
 *
 * Структура вывода:
 *
 * .imperia-catalog
 *
 *      button
 *
 *      dropdown
 *
 *          left
 *              категории
 *
 *          right
 *              подкатегории
 *
 *
 * ==========================================================
 */
final class MenuRenderer
{


	/**
	 * Сервис получения дерева.
	 */
	private CategoryCache $cache;



	/**
	 * ======================================================
	 * CONSTRUCTOR
	 * ======================================================
	 */
	public function __construct()
	{

		$this->cache =
			new CategoryCache();
	}



	/**
	 * ======================================================
	 * RENDER
	 * ======================================================
	 *
	 * Главный метод вывода меню.
	 */
	public function render(): void
	{


		/**
		 * Получаем дерево.
		 */
		$tree =
			$this->cache
			->getTree();



		/**
		 * Даже если категорий нет или есть служебная категория, но без товаров,
		 * кнопка каталога должна быть не видна.
		 *
		 */

		if (empty($tree)) return;

?>

		<div class="imperia-catalog ct-container">


			<!--
				Главная кнопка каталога.

				Всегда присутствует
				на странице.
			-->
			<button
				type="button"
				class="imperia-catalog__button"
				aria-expanded="false">
				Каталог товаров
			</button>



			<!--
				Выпадающее меню.

				По умолчанию скрыто CSS.

				JS добавит класс:
				
				imperia-catalog--open
			-->
			<div class="imperia-catalog__dropdown">


				<!--
					Левая колонка.

					Основные категории.
				-->
				<div class="imperia-catalog__left">

					<ul
						class="imperia-catalog__categories">


						<?php foreach ($tree as $category): ?>


							<li
								class="imperia-catalog__category
	<?= !empty($category['children'])
								? 'imperia-catalog__category--has-children'
								: ''; ?>"
								data-category-id="<?= esc_attr($category['id']); ?>">



								<!--
		Обычная ссылка категории.

		Клик по названию:
		переход в WooCommerce категорию.
	-->
								<a
									class="imperia-catalog__link"
									href="<?= esc_url($category['url']); ?>">


									<span class="imperia-catalog__category-name">

										<?= esc_html($category['name']); ?>

									</span>


								</a>



								<?php if (!empty($category['children'])): ?>


									<button
										type="button"
										class="imperia-catalog__toggle"

										data-category-id="<?= esc_attr($category['id']); ?>"

										aria-expanded="false"

										aria-controls="imperia-submenu-<?= esc_attr($category['id']); ?>"

										aria-label="Открыть подкатегории категории <?= esc_attr($category['name']); ?>">


										<svg
											class="imperia-catalog__icon"

											width="18"
											height="18"

											viewBox="0 0 24 24"

											fill="none"

											aria-hidden="true">


											<path
												d="M9 18l6-6-6-6"

												stroke="currentColor"

												stroke-width="2"

												stroke-linecap="round"

												stroke-linejoin="round" />


										</svg>


									</button>


								<?php endif; ?>


							</li>


						<?php endforeach; ?>


					</ul>


				</div>

				<!--
	Плавающая панель подкатегорий.

	JS будет:
	- наполнять HTML;
	- позиционировать напротив категории;
	- скрывать/показывать.
-->
				<div
					class="imperia-catalog__submenu"
					hidden>

				</div>

			</div>


		</div>



<?php

		/**
		 * ==================================================
		 * Передача дерева JavaScript.
		 * ==================================================
		 *
		 * JS получит:
		 *
		 * window.imperiaCatalogTree
		 *
		 * и сможет:
		 *
		 * - открывать подкатегории;
		 * - строить мобильный аккордеон;
		 * - работать без AJAX.
		 *
		 * ==================================================
		 */
		wp_add_inline_script(
			'imperia-catalog-menu',
			'window.imperiaCatalogTree = '
				. wp_json_encode($tree)
				. ';',
			'before'
		);

		if (
			defined('WP_DEBUG')
			&& WP_DEBUG
		) {
			imperia_log(
				'CATALOG MENU RENDERED'
			);
		}
	}
}
