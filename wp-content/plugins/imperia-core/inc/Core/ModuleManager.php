<?php

namespace Imperia\Core;

/**
 * ==========================================================
 * MODULE MANAGER
 * ==========================================================
 *
 * Отвечает только за:
 * - определение доступных модулей;
 * - фильтрацию по контексту;
 * - безопасную инициализацию.
 *
 * Модули НЕ выполняют бизнес-логику.
 * Они только регистрируют hooks.
 */
final class ModuleManager
{
	private bool $loaded = false;

	/**
	 * Реестр модулей.
	 *
	 * Формат:
	 * ModuleClass => allowed contexts
	 */
	private const MODULES = [

		'Imperia\\Modules\\Catalog\\Module' => [
			'frontend',
			'admin',
			'ajax',
		],
		'Imperia\\Modules\\Contacts\\Module' => [
			'frontend',
		],

		/*
     * Модуль интеграции с EDI.
     *
     * Сам обмен выполняет плагин EDI.
     *
     * Imperia Core лишь подключает
     * собственные фильтры и действия,
     * расширяющие стандартную работу EDI.
     */
		'Imperia\\Modules\\EDI\\Module' => [

			// Обмен идёт через обычный HTTP-запрос,
			// поэтому обязательно нужен frontend.
			'frontend',

			// Позже здесь появятся
			// настройки обмена.
			'admin',

			// Пригодится для ручной синхронизации
			// и AJAX-инструментов диагностики.
			'ajax',
		],

		/* 'Imperia\\Modules\\Checkout\\Module' => [
			'frontend',
		],

		'Imperia\\Modules\\Search\\Module' => [
			'frontend',
			'ajax',
		],

		'Imperia\\Modules\\Account\\Module' => [
			'frontend',
			'admin',
		], */

	];

	public function load(): void
	{
		if ($this->loaded) {
			return;
		}

		$this->loaded = true;

		$context = Context::type();

		//imperia_log('CONTEXT: ' . $context);

		/**
		 * Контексты,
		 * в которых система должна спать.
		 */
		if (
			$context === 'heartbeat'
			|| $context === 'cron'
			|| $context === 'cli'
		) {

			/* imperia_log(
				sprintf(
					'SLEEP MODE: %s',
					$context
				)
			); */

			return;
		}

		//imperia_log('CONTEXT: ' . $context);

		foreach (self::MODULES as $moduleClass => $allowedContexts) {

			if (!in_array($context, $allowedContexts, true)) {
				continue;
			}

			try {

				$module = new $moduleClass();

				if (!($module instanceof ModuleInterface)) {

					imperia_log(
						sprintf(
							'Invalid module: %s',
							$moduleClass
						)
					);

					continue;
				}

				/**
				 * ВАЖНО:
				 * init() должен только
				 * регистрировать hooks.
				 */
				$module->init();

				//imperia_log('CONTEXT: ' . Context::type());

				/* imperia_log(
					sprintf(
						'MODULE LOADED: %s',
						$moduleClass
					)
				); */
			} catch (\Throwable $e) {

				imperia_log(
					sprintf(
						'[Module ERROR] %s | %s',
						$moduleClass,
						$e->getMessage()
					)
				);
			}
		}
	}
}
