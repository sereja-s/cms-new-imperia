<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\Models;

use Imperia\Modules\Integration1C\Collections\ValidationMessageCollection;
use Imperia\Modules\Integration1C\Contracts\ModelInterface;

/**
 * ==========================================================================
 * PACKAGE VALIDATION RESULT
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Представляет результат проверки пакета обмена.
 *
 * Является неизменяемым объектом (Immutable),
 * содержащим все сообщения,
 * сформированные валидаторами.
 *
 * --------------------------------------------------------------------------
 * Ответственность
 * --------------------------------------------------------------------------
 *
 * • хранение результатов проверки;
 * • предоставление доступа к сообщениям;
 * • быстрый доступ к ошибкам, предупреждениям
 *   и информационным сообщениям;
 * • определение успешности проверки.
 *
 * --------------------------------------------------------------------------
 * Не отвечает за
 * --------------------------------------------------------------------------
 *
 * • выполнение проверки;
 * • создание сообщений;
 * • отображение сообщений;
 * • сохранение результатов.
 *
 * --------------------------------------------------------------------------
 * Инварианты
 * --------------------------------------------------------------------------
 *
 * • всегда содержит корректную коллекцию сообщений;
 * • после создания объект не изменяется (Immutable).
 *
 * --------------------------------------------------------------------------
 * Жизненный цикл
 * --------------------------------------------------------------------------
 *
 * Создаётся PackageValidator
 * после завершения проверки пакета обмена
 * и передаётся следующим сервисам системы.
 */
final class PackageValidationResult implements ModelInterface
{
	/**
	 * Коллекция сообщений проверки.
	 */
	private ValidationMessageCollection $messages;

	/**
	 * Создаёт результат проверки пакета.
	 *
	 * @param ValidationMessageCollection $messages
	 * Коллекция сообщений проверки.
	 */
	public function __construct(
		ValidationMessageCollection $messages
	) {
		$this->messages = $messages;
	}

	/**
	 * Возвращает все сообщения проверки.
	 */
	public function messages(): ValidationMessageCollection
	{
		return $this->messages;
	}

	/**
	 * Возвращает только ошибки.
	 */
	public function errors(): ValidationMessageCollection
	{
		return $this->messages->errors();
	}

	/**
	 * Возвращает только предупреждения.
	 */
	public function warnings(): ValidationMessageCollection
	{
		return $this->messages->warnings();
	}

	/**
	 * Возвращает только информационные сообщения.
	 */
	public function info(): ValidationMessageCollection
	{
		return $this->messages->info();
	}

	/**
	 * Проверяет,
	 * содержит ли результат ошибки.
	 */
	public function hasErrors(): bool
	{
		return !$this->errors()->isEmpty();
	}

	/**
	 * Проверяет,
	 * содержит ли результат предупреждения.
	 */
	public function hasWarnings(): bool
	{
		return !$this->warnings()->isEmpty();
	}

	/**
	 * Проверяет,
	 * содержит ли результат информационные сообщения.
	 */
	public function hasInfo(): bool
	{
		return !$this->info()->isEmpty();
	}

	/**
	 * Проверяет,
	 * успешно ли завершилась проверка.
	 *
	 * Проверка считается успешной,
	 * если не обнаружено ни одной ошибки.
	 */
	public function isSuccess(): bool
	{
		return !$this->hasErrors();
	}
}
