<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Filters;

use Imperia\Modules\EDI\Services\ImagePathResolver;

/**
 * ==========================================================
 * ImagePathFilter
 * ==========================================================
 *
 * Подготавливает пути изображений
 * до обработки их плагином EDI.
 */
final class ImagePathFilter
{

	private ImagePathResolver $resolver;

	public function __construct()
	{
		$this->resolver = new ImagePathResolver();

		/**
		 * Здесь позже зарегистрируем
		 * нужный фильтр EDI.
		 */
	}
}
