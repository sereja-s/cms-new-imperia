<?php

namespace Imperia\Core;

/**
 * ==========================================================
 * BOOTSTRAP
 * ==========================================================
 *
 * Единственная задача:
 * запустить ModuleManager.
 *
 * Вся логика определения контекста
 * находится в Context.
 *
 * Вся логика выбора модулей
 * находится в ModuleManager.
 */
final class Bootstrap
{
	private static ?self $instance = null;

	/**
	 * Защита от повторного запуска
	 */
	private bool $booted = false;

	private ModuleManager $modules;

	private function __construct() {}

	private function __clone() {}

	public static function instance(): self
	{
		return self::$instance ??= new self();
	}

	/**
	 * Главная точка запуска системы
	 */
	public function run(): void
	{
		if ($this->booted) {
			return;
		}

		$this->booted = true;

		$this->modules = new ModuleManager();

		imperia_log('BOOTSTRAP RUN');

		$this->modules->load();
	}
}
