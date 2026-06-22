<?php

namespace Imperia\Modules\Contacts\Hooks;

/**
 * ==========================================================
 * CONTACTS ASSETS LOADER
 * ==========================================================
 *
 * Подключение ресурсов модуля Contacts.
 *
 * Отвечает только за:
 *
 * - CSS;
 * - JavaScript.
 *
 * Не отвечает:
 *
 * - за HTML;
 * - за данные;
 * - за настройки;
 * - за ContactManager.
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
	 */
	public function enqueueAssets(): void
	{
		/**
		 * Не подключаем
		 * в административной панели.
		 */
		if (is_admin()) {
			return;
		}

		/**
		 * Базовый URL плагина.
		 */
		$baseUrl =
			plugin_dir_url(
				IMPERIA_PLUGIN_FILE
			);

		/**
		 * Базовый путь плагина.
		 */
		$basePath =
			plugin_dir_path(
				IMPERIA_PLUGIN_FILE
			);

		/**
		 * Физические пути.
		 */
		$cssFile =
			$basePath
			. 'modules/Contacts/Assets/contacts.css';

		$jsFile =
			$basePath
			. 'modules/Contacts/Assets/contacts.js';

		/**
		 * URL файлов.
		 */
		$cssUrl =
			$baseUrl
			. 'modules/Contacts/Assets/contacts.css';

		$jsUrl =
			$baseUrl
			. 'modules/Contacts/Assets/contacts.js';

		/**
		 * ==================================================
		 * CSS
		 * ==================================================
		 */
		wp_enqueue_style(
			'imperia-contacts',
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
		 */
		wp_enqueue_script(
			'imperia-contacts',
			$jsUrl,
			[],
			file_exists($jsFile)
				? filemtime($jsFile)
				: null,
			true
		);
	}
}
