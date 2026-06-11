<?php

namespace Imperia\Core;

/**
 * ==========================================================
 * CONTEXT
 * ==========================================================
 *
 * Единственный источник информации
 * о текущем окружении WordPress.
 */
final class Context
{
	public static function type(): string
	{
		if (self::is_cli()) {
			return 'cli';
		}

		if (self::is_ajax() && self::is_heartbeat()) {
			return 'heartbeat';
		}

		if (self::is_ajax()) {
			return 'ajax';
		}

		if (self::is_cron()) {
			return 'cron';
		}

		if (self::is_rest()) {
			return 'rest';
		}

		if (self::is_admin()) {
			return 'admin';
		}

		return 'frontend';
	}

	public static function is_admin(): bool
	{
		return is_admin()
			&& !self::is_ajax()
			&& !self::is_cron()
			&& !self::is_rest();
	}

	public static function is_ajax(): bool
	{
		return defined('DOING_AJAX') && DOING_AJAX;
	}

	public static function is_cron(): bool
	{
		return defined('DOING_CRON') && DOING_CRON;
	}

	public static function is_rest(): bool
	{
		return defined('REST_REQUEST') && REST_REQUEST;
	}

	public static function is_cli(): bool
	{
		return \defined('WP_CLI') && \WP_CLI;
	}

	public static function is_heartbeat(): bool
	{
		return self::is_ajax()
			&& isset($_REQUEST['action'])
			&& $_REQUEST['action'] === 'heartbeat';
	}

	public static function is_frontend(): bool
	{
		return self::type() === 'frontend';
	}
}
