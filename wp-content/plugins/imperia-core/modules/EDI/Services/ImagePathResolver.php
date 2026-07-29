<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Services;

/**
 * ==========================================================
 * ImagePathResolver
 * ==========================================================
 *
 * Отвечает исключительно за построение
 * корректного пути к изображениям,
 * полученным из 1С.
 *
 * Никаких WordPress Hooks.
 * Никакой загрузки файлов.
 * Только вычисление пути.
 */
final class ImagePathResolver
{

	/**
	 * Приводит путь изображения
	 * к единому формату.
	 *
	 * Например:
	 *
	 * import_files\Фото\1.jpg
	 *
	 * →
	 *
	 * import_files/Фото/1.jpg
	 */
	public function normalize(string $path): string
	{
		$path = str_replace('\\', '/', $path);

		$path = ltrim($path, '/');

		return $path;
	}
}
