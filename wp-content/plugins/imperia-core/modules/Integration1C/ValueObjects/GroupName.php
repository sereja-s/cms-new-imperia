<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\ValueObjects;

use Imperia\Modules\Integration1C\Contracts\ValueObjectInterface;
use InvalidArgumentException;

/**
 * ==========================================================================
 * GROUP NAME
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Представляет название группы товаров,
 * полученное из информационной системы 1С.
 *
 * Группа 1С используется для поиска
 * соответствующей категории WooCommerce
 * во время подготовки обмена.
 *
 * Данный объект не знает:
 *
 * • существует ли такая категория на сайте;
 * • какой категории WooCommerce она соответствует;
 * • где находится категория в дереве каталога.
 *
 * Он представляет только название группы,
 * пришедшее из 1С.
 *
 * --------------------------------------------------------------------------
 * Инварианты
 * --------------------------------------------------------------------------
 *
 * • название не может быть пустым;
 * • пробелы по краям автоматически удаляются;
 * • объект является immutable.
 */
final class GroupName implements ValueObjectInterface
{
	/**
	 * Название группы.
	 */
	private string $groupName;

	/**
	 * Создаёт объект названия группы.
	 *
	 * @param string $groupName Название группы, полученное из 1С.
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(string $groupName)
	{
		$groupName = trim($groupName);

		if ($groupName === '') {
			throw new InvalidArgumentException(
				'Название группы не может быть пустым.'
			);
		}

		$this->groupName = $groupName;
	}

	/**
	 * Возвращает название группы.
	 */
	public function value(): string
	{
		return $this->groupName;
	}

	/**
	 * Сравнивает группы двух товаров.
	 */
	public function equals(GroupName $other): bool
	{
		return $this->groupName === $other->value();
	}

	/**
	 * Возвращает группу в виде строки.
	 */
	public function __toString(): string
	{
		return $this->groupName;
	}
}
