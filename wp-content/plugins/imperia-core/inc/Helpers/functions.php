<?php

/**
 * ==========================================================
 * IMPERIA CORE HELPERS
 * ==========================================================
 *
 * Здесь находятся только ПРЕДЕЛЬНО простые функции,
 * которые:
 * - не требуют классов
 * - не зависят от Context/Bootstrap
 * - безопасны в любом WordPress request lifecycle
 *
 * Это "нулевой слой" системы.
 */

if (!function_exists('imperia_log')) {

	/**
	 * Унифицированное логирование системы.
	 *
	 * Особенности:
	 * - работает только при WP_DEBUG + WP_DEBUG_LOG
	 * - не ломает выполнение кода
	 * - безопасно вызывается из любого места
	 *
	 * НЕ использовать для бизнес-логики.
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
}
