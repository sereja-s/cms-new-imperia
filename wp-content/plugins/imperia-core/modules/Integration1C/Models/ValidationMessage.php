<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\Models;

use Imperia\Modules\Integration1C\Contracts\ModelInterface;
use Imperia\Modules\Integration1C\Enums\ValidationMessageCode;
use Imperia\Modules\Integration1C\Enums\ValidationMessageLevel;
use InvalidArgumentException;

/**
 * ==========================================================================
 * VALIDATION MESSAGE
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Представляет одно сообщение,
 * сформированное во время проверки данных обмена.
 *
 * Сообщение состоит из:
 *
 * • уровня;
 * • кода;
 * • текста.
 *
 * --------------------------------------------------------------------------
 * Ответственность
 * --------------------------------------------------------------------------
 *
 * • хранение информации о результате проверки;
 * • предоставление доступа к данным сообщения;
 * • безопасное сравнение сообщений.
 *
 * --------------------------------------------------------------------------
 * Не отвечает за
 * --------------------------------------------------------------------------
 *
 * • выполнение проверки;
 * • отображение сообщения;
 * • локализацию текста;
 * • запись журналов.
 *
 * --------------------------------------------------------------------------
 * Инварианты
 * --------------------------------------------------------------------------
 *
 * • всегда содержит корректный ValidationMessageLevel;
 * • всегда содержит корректный ValidationMessageCode;
 * • текст сообщения не может быть пустым;
 * • после создания объект не изменяется (Immutable).
 *
 * --------------------------------------------------------------------------
 * Жизненный цикл
 * --------------------------------------------------------------------------
 *
 * Создаётся ValidationMessageFactory
 * и используется коллекцией сообщений проверки
 * на протяжении процесса обмена.
 */
final class ValidationMessage implements ModelInterface
{
	/**
	 * Уровень сообщения.
	 */
	private ValidationMessageLevel $level;

	/**
	 * Код сообщения.
	 */
	private ValidationMessageCode $code;

	/**
	 * Текст сообщения.
	 */
	private string $message;

	/**
	 * Создаёт сообщение проверки.
	 *
	 * @param ValidationMessageLevel $level   Уровень сообщения.
	 * @param ValidationMessageCode  $code    Код сообщения.
	 * @param string                 $message Текст сообщения.
	 *
	 * @throws InvalidArgumentException
	 * Если текст сообщения пустой.
	 */
	public function __construct(
		ValidationMessageLevel $level,
		ValidationMessageCode $code,
		string $message
	) {
		$message = trim($message);

		if ($message === '') {
			throw new InvalidArgumentException(
				'Текст сообщения не может быть пустым.'
			);
		}

		$this->level   = $level;
		$this->code    = $code;
		$this->message = $message;
	}

	/**
	 * Возвращает уровень сообщения.
	 */
	public function level(): ValidationMessageLevel
	{
		return $this->level;
	}

	/**
	 * Возвращает код сообщения.
	 */
	public function code(): ValidationMessageCode
	{
		return $this->code;
	}

	/**
	 * Возвращает текст сообщения.
	 */
	public function message(): string
	{
		return $this->message;
	}

	/**
	 * Проверяет,
	 * является ли сообщение ошибкой.
	 */
	public function isError(): bool
	{
		return $this->level === ValidationMessageLevel::ERROR;
	}

	/**
	 * Проверяет,
	 * является ли сообщение предупреждением.
	 */
	public function isWarning(): bool
	{
		return $this->level === ValidationMessageLevel::WARNING;
	}

	/**
	 * Проверяет,
	 * является ли сообщение информационным.
	 */
	public function isInfo(): bool
	{
		return $this->level === ValidationMessageLevel::INFO;
	}

	/**
	 * Сравнивает два сообщения проверки.
	 */
	public function equals(
		ValidationMessage $other
	): bool {

		return
			$this->level === $other->level()
			&& $this->code === $other->code()
			&& $this->message === $other->message();
	}
}
