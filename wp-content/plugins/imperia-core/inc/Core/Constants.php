<?php

namespace Imperia\Core;

/**
 * Централизованное хранилище системных констант.
 *
 * Используется для:
 * - путей проекта;
 * - версии плагина;
 * - будущих системных настроек.
 */
final class Constants
{
	/**
	 * Версия плагина.
	 *
	 * Должна совпадать с Version
	 * в imperia-core.php
	 */
	public const VERSION = '0.1.0';

	/**
	 * Абсолютный путь к каталогу модулей.
	 */
	public const MODULES_PATH = __DIR__ . '/../../modules/';

	private function __construct() {}
}
