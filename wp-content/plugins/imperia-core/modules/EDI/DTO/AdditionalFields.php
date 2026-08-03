<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\DTO;

/**
 * ==========================================================
 * AdditionalFields
 * ==========================================================
 *
 * DTO (Data Transfer Object),
 * содержащий значения дополнительных реквизитов товара,
 * извлечённых из CommerceML.
 *
 * ----------------------------------------------------------
 * Зачем нужен этот класс
 * ----------------------------------------------------------
 *
 * Можно было бы вернуть обычный массив:
 *
 * [
 *     'siteSku' => ...,
 *     'siteName' => ...,
 *     'shortDescription' => ...
 * ]
 *
 * Но массив имеет недостатки:
 *
 * • строковые ключи легко написать с ошибкой;
 * • IDE не подсказывает допустимые поля;
 * • невозможно определить типы значений.
 *
 * Поэтому используется отдельный объект.
 *
 * Он ничего не вычисляет.
 *
 * Его задача —
 * только хранить найденные значения.
 */
final class AdditionalFields
{
	/**
	 * Название для сайта.
	 */
	private ?string $siteName = null;

	/**
	 * Артикул для сайта.
	 */
	private ?string $siteSku = null;

	/**
	 * Короткое описание.
	 */
	private ?string $shortDescription = null;

	/**
	 * Сохранить название для сайта.
	 */
	public function setSiteName(?string $value): void
	{
		$this->siteName = $value;
	}

	/**
	 * Получить название для сайта.
	 */
	public function siteName(): ?string
	{
		return $this->siteName;
	}

	/**
	 * Сохранить артикул для сайта.
	 */
	public function setSiteSku(?string $value): void
	{
		$this->siteSku = $value;
	}

	/**
	 * Получить артикул для сайта.
	 */
	public function siteSku(): ?string
	{
		return $this->siteSku;
	}

	/**
	 * Сохранить короткое описание.
	 */
	public function setShortDescription(?string $value): void
	{
		$this->shortDescription = $value;
	}

	/**
	 * Получить короткое описание.
	 */
	public function shortDescription(): ?string
	{
		return $this->shortDescription;
	}
}
