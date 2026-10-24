<?php

declare(strict_types=1);

namespace Imperia\Modules\ProductNotice\Hooks;

/**
 * Управляет подключением CSS модуля ProductNotice.
 *
 * Принципы:
 * - CSS подключается только на странице отдельного товара;
 * - используется константа IMPERIA_PLUGIN_FILE;
 * - версия файла определяется через filemtime() для сброса кеша;
 * - отсутствие файла не должно приводить к фатальной ошибке.
 */
final class AssetsLoader
{
	/**
	 * Имя стиля в очереди WordPress.
	 *
	 * Уникальный идентификатор позволяет избежать конфликтов
	 * с другими стилями WordPress, WooCommerce и Blocksy.
	 */
	private const STYLE_HANDLE = 'imperia-product-notice';

	/**
	 * Регистрирует обработчик подключения стилей.
	 */
	public function register(): void
	{
		add_action(
			'wp_enqueue_scripts',
			[$this, 'enqueueStyles']
		);
	}

	/**
	 * Добавляет CSS в очередь WordPress.
	 */
	public function enqueueStyles(): void
	{
		// В административной части сайта эти стили не нужны.
		if (is_admin()) {
			return;
		}

		// Ограничиваем загрузку страницами отдельных товаров.
		if (
			!function_exists('is_product')
			|| !is_product()
		) {
			return;
		}

		/*
         * Константа должна определяться главным файлом Imperia Core.
         * Если она не определена, безопаснее отказаться от подключения.
         */
		if (!defined('IMPERIA_PLUGIN_FILE')) {
			return;
		}

		/*
         * Получаем физический путь к CSS-файлу.
         *
         * plugin_dir_path() возвращает путь к каталогу плагина
         * и корректно работает независимо от расположения WordPress.
         */
		$cssPath = plugin_dir_path(IMPERIA_PLUGIN_FILE)
			. 'modules/ProductNotice/Assets/product-notice.css';

		/*
         * Получаем URL того же файла для браузера.
         */
		$cssUrl = plugin_dir_url(IMPERIA_PLUGIN_FILE)
			. 'modules/ProductNotice/Assets/product-notice.css';

		/*
         * Проверяем наличие файла до регистрации стиля.
         * Это помогает обнаружить ошибку при копировании модуля.
         */
		if (!is_file($cssPath)) {
			if (function_exists('imperia_log')) {
				imperia_log(
					sprintf(
						'ProductNotice: CSS-файл не найден: %s',
						$cssPath
					)
				);
			}

			return;
		}

		/*
         * filemtime() меняет версию стиля после изменения CSS.
         * Благодаря этому браузер обычно загружает обновлённый файл,
         * а не использует старую копию из кеша.
         */
		$version = filemtime($cssPath);

		/*
         * Если получить время изменения не удалось, используем null.
         * WordPress тогда применит стандартное поведение версии стиля.
         */
		if ($version === false) {
			$version = null;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			$cssUrl,
			[],
			$version
		);
	}
}
