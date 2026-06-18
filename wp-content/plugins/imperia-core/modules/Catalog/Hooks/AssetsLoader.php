<?php

namespace Imperia\Modules\Catalog\Hooks;

/**
 * ==========================================================
 * ASSETS LOADER
 * ==========================================================
 *
 * Отвечает только за подключение:
 *
 * - CSS
 * - JavaScript
 *
 * Не занимается:
 *
 * - HTML
 * - категориями
 * - кэшем
 * - AJAX
 */
final class AssetsLoader
{
	/**
	 * Регистрация hook.
	 */
	public function register(): void
	{
		add_action(
			'wp_enqueue_scripts',
			[$this, 'enqueueAssets']
		);
	}

	/**
	 * Подключение ресурсов.
	 */
	public function enqueueAssets(): void
	{
		$baseUrl =
			plugin_dir_url(
				IMPERIA_PLUGIN_FILE
			);

		wp_enqueue_style(
			'imperia-catalog-menu',
			$baseUrl .
				'modules/Catalog/Assets/catalog-menu.css',
			[],
			'1.0.0'
		);

		wp_enqueue_script(
			'imperia-catalog-menu',
			$baseUrl .
				'modules/Catalog/Assets/catalog-menu.js',
			[],
			'1.0.0',
			true
		);
	}
}
