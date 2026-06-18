<?php

namespace Imperia\Modules\Catalog\Hooks;

use Imperia\Modules\Catalog\Views\MenuRenderer;

/**
 * ==========================================================
 * HEADER BUTTON
 * ==========================================================
 *
 * Компонент интеграции каталога
 * с областью шапки сайта.
 *
 * На этапе разработки используется
 * стандартный WordPress hook wp_footer
 * как тестовая точка вывода.
 *
 * После определения подходящего hook
 * темы Blocksy будет изменён только
 * этот класс.
 *
 * Остальная архитектура останется без изменений:
 *
 * HeaderButton
 *      ↓
 * MenuRenderer
 *      ↓
 * CategoryCache
 *      ↓
 * CategoryTree
 *
 * ==========================================================
 */
final class HeaderButton
{
	public function register(): void
	{
		add_action(
			'wp_footer',
			[$this, 'render']
		);
	}

	public function render(): void
	{
		(new MenuRenderer())
			->render();
	}
}
