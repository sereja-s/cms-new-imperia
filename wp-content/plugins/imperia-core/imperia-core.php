<?php

/**
 * Plugin Name: Imperia Core
 * Description: Modular architecture plugin
 * Version: 0.1.0
 * Author: Imperia
 * Requires PHP: 8.3
 * Requires at least: 6.8
 * Text Domain: imperia-core
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * это единственная helper-функция в файле imperia-core.php
 */
if (!function_exists('imperia_log')) {

	/**
	 * Запись сообщения в журнал отладки WordPress.
	 *
	 * Логирование работает только если:
	 * - включён WP_DEBUG
	 * - включён WP_DEBUG_LOG
	 *
	 * В продакшене функция молча завершится.
	 */
	function imperia_log(string $message): void
	{
		if (
			defined('WP_DEBUG')
			&& WP_DEBUG
			&& defined('WP_DEBUG_LOG')
			&& WP_DEBUG_LOG
		) {
			error_log('[Imperia] ' . $message);
		}
	}

	/* function imperia_log(string $message): void
	{
		if (
			defined('WP_DEBUG')
			&& WP_DEBUG
			&& defined('WP_DEBUG_LOG')
			&& WP_DEBUG_LOG
		) {

			error_log(sprintf(
				'[Imperia] URI=%s | ACTION=%s | AJAX=%s | CRON=%s | %s',
				$_SERVER['REQUEST_URI'] ?? 'unknown',
				$_REQUEST['action'] ?? 'none',
				(defined('DOING_AJAX') && DOING_AJAX) ? 'yes' : 'no',
				(defined('DOING_CRON') && DOING_CRON) ? 'yes' : 'no',
				$message
			));
		}
	} */
}

/**
 * ==========================================================
 * 1. ОПРЕДЕЛЯЕМ КРИТИЧЕСКИЕ ФАЙЛЫ ЯДРА
 * ==========================================================
 *
 * Это "обязательный минимум" для работы плагина.
 * Если хотя бы одного файла нет — плагин НЕ должен стартовать.
 */

$coreFiles = [
	'autoloader' => __DIR__ . '/inc/Core/Autoloader.php',
	'bootstrap' => __DIR__ . '/inc/Core/Bootstrap.php',
];

/**
 * ==========================================================
 * 2. ПРОВЕРКА ЦЕЛОСТНОСТИ ЯДРА (FAIL-FAST SAFETY LAYER)
 * ==========================================================
 *
 * Здесь мы НЕ даём PHP упасть с fatal error.
 * Вместо этого:
 * - фиксируем проблему
 * - пишем в лог
 * - мягко останавливаем плагин
 */

foreach ($coreFiles as $name => $file) {

	if (!is_file($file)) {

		/**
		 * Логируем критическую ошибку ядра
		 * (WordPress debug.log или error_log сервера)
		 */
		imperia_log(
			sprintf(
				'CRITICAL: Missing core file [%s]: %s',
				$name,
				$file
			)
		);
		/**
		 * Останавливаем выполнение плагина.
		 *
		 * ВАЖНО:
		 * - WordPress не падает
		 * - но плагин НЕ активируется
		 */
		return;
	}
}

/**
 * ==========================================================
 * 3. ПОДКЛЮЧЕНИЕ ЯДРА (ТОЛЬКО ЕСЛИ ВСЁ ОК)
 * ==========================================================
 *
 * Здесь уже безопасно подключаем файлы,
 * потому что мы убедились, что они существуют.
 */

require_once $coreFiles['autoloader'];
require_once $coreFiles['bootstrap'];

/**
 * ==========================================================
 * 4. ЗАПУСК ПЛАГИНА (ЕДИНСТВЕННАЯ ТОЧКА СТАРТА)
 * ==========================================================
 *
 * Вся бизнес-логика начинается ТОЛЬКО отсюда.
 * Autoloader уже подключён.
 * Bootstrap уже доступен.
 */

add_action(
	'plugins_loaded',
	static function (): void {

		if (!class_exists(\Imperia\Core\Bootstrap::class)) {
			imperia_log(
				'CRITICAL: Core Bootstrap class not found after include'
			);

			return;
		}

		\Imperia\Core\Bootstrap::instance()->run();
	}
);

imperia_log('PLUGIN FILE LOADED');
