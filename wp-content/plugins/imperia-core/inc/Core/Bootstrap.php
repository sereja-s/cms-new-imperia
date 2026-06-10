<?php

namespace Imperia\Core;

/**
 * ==========================================================
 * BOOTSTRAP
 * ==========================================================
 *
 * Главная точка запуска системы.
 *
 * Задачи Bootstrap:
 * - запуск ядра плагина;
 * - инициализация основных сервисов;
 * - запуск менеджера модулей;
 * - контроль единственного старта системы.
 *
 * ВАЖНО:
 * Bootstrap НЕ должен содержать бизнес-логику.
 * Его задача — только собрать и запустить систему.
 */
final class Bootstrap
{
	/**
	 * Единственный экземпляр Bootstrap.
	 */
	private static ?self $instance = null;

	/**
	 * Флаг запуска системы.
	 *
	 * Защищает от повторного вызова run().
	 */
	private bool $booted = false;

	/**
	 * Менеджер модулей.
	 */
	private ModuleManager $modules;

	/**
	 * Запрещаем создание объекта извне.
	 *
	 * Bootstrap должен запускаться
	 * только через Bootstrap::instance().
	 */
	private function __construct() {}

	/**
	 * Запрещаем клонирование Singleton.
	 */
	private function __clone() {}

	/**
	 * Получение единственного экземпляра Bootstrap.
	 */
	public static function instance(): self
	{
		return self::$instance ??= new self();
	}

	/**
	 * ======================================================
	 * ЗАПУСК СИСТЕМЫ
	 * ======================================================
	 *
	 * Выполняется один раз после plugins_loaded.
	 *
	 * Последовательность:
	 * 1. Проверяем, не запускалось ли ядро ранее.
	 * 2. Создаём менеджер модулей.
	 * 3. Загружаем зарегистрированные модули.
	 */
	public function run(): void
	{
		/**
		 * Защита от повторного запуска.
		 *
		 * Если кто-то случайно вызовет:
		 *
		 * Bootstrap::instance()->run();
		 *
		 * второй раз — система просто проигнорирует вызов.
		 */
		if ($this->booted) {

			imperia_log(
				'WARNING: Bootstrap run() called more than once'
			);

			return;
		}

		/**
		 * Отмечаем систему как запущенную.
		 */
		$this->booted = true;

		imperia_log('Bootstrap started');

		/**
		 * Создаём менеджер модулей.
		 */
		$this->modules = new ModuleManager();

		/**
		 * Загружаем все активные модули.
		 */
		$this->modules->load();

		imperia_log('Bootstrap completed');
	}
}
