<?php

/**
 * Plugin Name: Imperia Core
 * Description: Modular architecture plugin (sleeping system)
 * Version: 0.1.0
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * ==========================================================
 * PLUGIN ROOT CONSTANT
 * ==========================================================
 *
 * Сохраняем абсолютный путь к главному файлу плагина.
 *
 * Константа используется для:
 *
 * - получения URL плагина;
 * - подключения CSS и JavaScript;
 * - работы модулей с файлами плагина.
 *
 * __FILE__ указывает на текущий файл:
 *
 * imperia-core.php
 *
 * ==========================================================
 */
if (
	! defined('IMPERIA_PLUGIN_FILE')
) {
	define(
		'IMPERIA_PLUGIN_FILE',
		__FILE__
	);
}

/**
 * ==========================================================
 * HELPERS
 * ==========================================================
 * Подключаем чистые функции (логирование и утилиты).
 * Без них ядро не должно зависеть от классов.
 */
require_once __DIR__ . '/inc/Helpers/functions.php';

/**
 * ==========================================================
 * AUTOLOADER + BOOTSTRAP
 * ==========================================================
 * Подключаем только инфраструктуру.
 * Никакой логики выполнения здесь нет.
 */
$coreFiles = [
	'autoloader' => __DIR__ . '/inc/Core/Autoloader.php',
	'bootstrap'  => __DIR__ . '/inc/Core/Bootstrap.php',
];

/**
 * Fail-fast: если ядро повреждено — плагин не стартует.
 * Это защищает WordPress от частично сломанного состояния.
 */
foreach ($coreFiles as $name => $file) {

	if (!is_file($file)) {

		if (function_exists('imperia_log')) {
			imperia_log("Critical missing core file: {$name}");
		}

		return;
	}

	require_once $file;
}

/**
 * ==========================================================
 * BOOTSTRAP ENTRYPOINT
 * ==========================================================
 *
 * ВАЖНО:
 * Мы не запускаем систему сразу.
 * Только регистрируем запуск после загрузки всех плагинов.
 *
 * Это стандартный и безопасный WordPress lifecycle hook.
 */
add_action('plugins_loaded', static function (): void {

	/**
	 * Дополнительная защита:
	 * если класс ядра не загрузился — ничего не делаем.
	 */
	if (!class_exists(\Imperia\Core\Bootstrap::class)) {

		if (function_exists('imperia_log')) {
			imperia_log('Bootstrap class not found');
		}

		return;
	}

	/**
	 * Старт системы.
	 * Дальше всё управление переходит в Bootstrap → Context → ModuleManager
	 */
	\Imperia\Core\Bootstrap::instance()->run();
});

//imperia_log('ENTRY POINT HIT');
