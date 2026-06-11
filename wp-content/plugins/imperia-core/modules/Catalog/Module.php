<?php

namespace Imperia\Modules\Catalog;

use Imperia\Core\ModuleInterface;

final class Module implements ModuleInterface
{
	public function init(): void
	{
		/**
		 * Пока регистрируем тестовый hook.
		 *
		 * Это демонстрирует правильную архитектуру:
		 * модуль не работает сразу,
		 * а ждёт вызова WordPress.
		 */
		add_action(
			'wp',
			[$this, 'register']
		);
	}

	public function register(): void
	{
		if (
			defined('WP_DEBUG')
			&& WP_DEBUG
		) {
			imperia_log('Catalog hook executed');
		}
	}
}
