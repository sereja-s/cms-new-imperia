<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\ValueObjects;

use Imperia\Modules\Integration1C\Contracts\ValueObjectInterface;
use InvalidArgumentException;

/**
 * ==========================================================================
 * IMAGE SOURCE
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Представляет источник изображения товара, полученного из 1С.
 *
 * Источник изображения хранится в виде строки.
 * Интерпретация этой строки зависит от используемого способа обмена
 * (HTTP, FTP, ZIP-архив, локальная папка, API и т.д.).
 *
 * Данный объект не знает, каким способом изображение будет получено.
 * Он лишь гарантирует корректность хранения значения.
 *
 * --------------------------------------------------------------------------
 * Примеры допустимых значений
 * --------------------------------------------------------------------------
 *
 * https://example.com/images/drill.jpg
 *
 * images/drill.jpg
 *
 * uploads/tools/drill.jpg
 *
 * 7d93ef3b-48cb-4c11-a9d1-acde48001122
 *
 * --------------------------------------------------------------------------
 * Инварианты
 * --------------------------------------------------------------------------
 *
 * - источник изображения не может быть пустым;
 * - пробелы в начале и конце автоматически удаляются;
 * - объект является immutable.
 */
final class ImageSource implements ValueObjectInterface
{
	/**
	 * Источник изображения.
	 */
	private string $imageSource;

	/**
	 * Создаёт объект источника изображения.
	 *
	 * @param string $imageSource Источник изображения, полученного из 1С.
	 *
	 * @throws InvalidArgumentException Если значение пустое.
	 */
	public function __construct(string $imageSource)
	{
		$imageSource = trim($imageSource);

		if ($imageSource === '') {
			throw new InvalidArgumentException(
				'Источник изображения не может быть пустым.'
			);
		}

		$this->imageSource = $imageSource;
	}

	/**
	 * Возвращает источник изображения.
	 *	 
	 */
	public function value(): string
	{
		return $this->imageSource;
	}

	/**
	 * Сравнивает источники изображения двух товаров.
	 *
	 * @param ImageSource $other
	 *	 
	 */
	public function equals(ImageSource $other): bool
	{
		return $this->imageSource === $other->value();
	}

	/**
	 * Возвращает источник изображения в виде строки.
	 *	 
	 */
	public function __toString(): string
	{
		return $this->imageSource;
	}
}
