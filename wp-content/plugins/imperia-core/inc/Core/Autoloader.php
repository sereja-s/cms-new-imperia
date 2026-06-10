<?php

/**
 * AUTOLOADER
 *
 * Отвечает только за:
 * - сопоставление namespace и пути;
 * - поиск файла класса;
 * - подключение найденного файла;
 * - логирование ошибок загрузки.
 *
 * Не содержит бизнес-логики.
 */
spl_autoload_register(
	static function (string $class): void {

		$prefixes = [

			'Imperia\\Core\\'    => dirname(__DIR__) . '/Core/',
			'Imperia\\Modules\\' => dirname(__DIR__, 2) . '/modules/',

		];

		foreach ($prefixes as $prefix => $baseDir) {

			$len = strlen($prefix);

			if (strncmp($class, $prefix, $len) !== 0) {
				continue;
			}

			$relative = substr($class, $len);

			$file = $baseDir
				. str_replace('\\', '/', $relative)
				. '.php';

			if (is_file($file)) {
				require_once $file;
				return;
			}

			imperia_log(
				sprintf(
					'CRITICAL: [Autoload] Missing file [%s]: %s',
					$class,
					$file
				)
			);

			break;
		}
	}
);
