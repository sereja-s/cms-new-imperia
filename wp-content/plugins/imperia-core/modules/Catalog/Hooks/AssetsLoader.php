<?php

namespace Imperia\Modules\Catalog\Hooks;

use Imperia\Modules\Catalog\Services\CategoryCache;

/**
 * ==========================================================
 * ASSETS LOADER
 * ==========================================================
 *
 * Подключение ресурсов каталога:
 *
 * - CSS
 * - JavaScript
 *
 * Не отвечает за:
 *
 * - HTML;
 * - категории;
 * - кэш;
 * - AJAX.
 *
 * ==========================================================
 */
final class AssetsLoader
{


	/**
	 * ======================================================
	 * REGISTER
	 * ======================================================
	 */
	public function register(): void
	{


		add_action(
			'wp_enqueue_scripts',
			[
				$this,
				'enqueueAssets'
			]
		);
	}



	/**
	 * ======================================================
	 * ENQUEUE ASSETS
	 * ======================================================
	 *
	 * Регистрация CSS и JS.
	 *
	 * Важно:
	 *
	 * JS должен быть зарегистрирован
	 * до вызова:
	 *
	 * wp_add_inline_script()
	 *
	 * в MenuRenderer.
	 *
	 * ======================================================
	 */
	public function enqueueAssets(): void
	{


		/**
		 * Не грузим ресурсы
		 * в административной части.
		 */
		if (
			is_admin()
		) {

			return;
		}



		/**
		 * URL плагина.
		 */
		$baseUrl =
			plugin_dir_url(
				IMPERIA_PLUGIN_FILE
			);



		/**
		 * Физический путь.
		 *
		 * Нужен для filemtime().
		 */
		$basePath =
			plugin_dir_path(
				IMPERIA_PLUGIN_FILE
			);



		/**
		 * Пути файлов.
		 */
		$cssFile =
			$basePath
			. 'modules/Catalog/Assets/catalog-menu.css';



		$jsFile =
			$basePath
			. 'modules/Catalog/Assets/catalog-menu.js';



		/**
		 * URL файлов.
		 */
		$cssUrl =
			$baseUrl
			. 'modules/Catalog/Assets/catalog-menu.css';



		$jsUrl =
			$baseUrl
			. 'modules/Catalog/Assets/catalog-menu.js';




		/**
		 * ==================================================
		 * CSS
		 * ==================================================
		 *
		 * filemtime()
		 *
		 * автоматически меняет версию
		 * после изменения файла.
		 *
		 * Было:
		 *
		 * catalog-menu.css?ver=1.0.0
		 *
		 * Стало:
		 *
		 * catalog-menu.css?ver=1782030000
		 *
		 * Браузер не использует старый кеш.
		 *
		 */
		wp_enqueue_style(
			'imperia-catalog-menu',
			$cssUrl,
			[],
			file_exists($cssFile)
				? filemtime($cssFile)
				: null
		);




		/**
		 * ==================================================
		 * JAVASCRIPT
		 * ==================================================
		 *
		 * Загружаем перед закрытием body.
		 *
		 * Этот handle используется
		 * MenuRenderer:
		 *
		 * wp_add_inline_script(
		 *     'imperia-catalog-menu',
		 *     ...
		 * );
		 *
		 */
		wp_enqueue_script(
			'imperia-catalog-menu',
			$jsUrl,
			[],
			file_exists($jsFile)
				? filemtime($jsFile)
				: null,
			true
		);

		/* if (
			defined('WP_DEBUG')
			&& WP_DEBUG
		) {
			imperia_log(
				'CATALOG JS ENQUEUED'
			);
		} */
	}
}
