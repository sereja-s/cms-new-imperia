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

		'Imperia\\Modules\\ProductNotice\\Module' => [
			'frontend',
		],

		/*
		 * Модуль интеграции с EDI.
		 *
		 * Сам обмен выполняет оригинальный плагин EDI.
		 *
		 * Imperia Core подключает только собственные
		 * расширения и hooks.
		 *
		 * Контекст "edi" предназначен для запросов
		 * непосредственно к endpoint:
		 *
		 *     /edi/1c
		 *
		 * Это позволяет не загружать EDI-модуль
		 * на обычных frontend-страницах магазина.
		 */
		'Imperia\\Modules\\EDI\\Module' => [
			'edi',
		],

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

			/* imperia_log(
				sprintf(
					'IMPERIA MODULE CHECK | context=%s | module=%s',
					$context,
					$moduleClass
				)
			); */

			if (!in_array($context, $allowedContexts, true)) {
				continue;
			}

			/* imperia_log(
				sprintf(
					'IMPERIA MODULE LOAD | context=%s | module=%s',
					$context,
					$moduleClass
				)
			); */

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
