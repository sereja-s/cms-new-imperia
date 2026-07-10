<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\Collections;

use Closure;
use Countable;
use IteratorAggregate;
use ArrayIterator;
use Traversable;

use Imperia\Modules\Integration1C\Contracts\CollectionInterface;
use Imperia\Modules\Integration1C\Models\ValidationMessage;
use Imperia\Modules\Integration1C\Enums\ValidationMessageCode;
use Imperia\Modules\Integration1C\Enums\ValidationMessageLevel;


/**
 * ==========================================================================
 * VALIDATION MESSAGE COLLECTION
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Представляет коллекцию сообщений проверки,
 * сформированных во время обработки пакета обмена.
 *
 * Коллекция гарантирует,
 * что содержит только объекты ValidationMessage.
 *
 * --------------------------------------------------------------------------
 * Ответственность
 * --------------------------------------------------------------------------
 * • хранение сообщений проверки;
 * • безопасное добавление сообщений;
 * • фильтрация сообщений;
 * • подсчёт сообщений;
 * • итерация по сообщениям.
 *
 * --------------------------------------------------------------------------
 * Не отвечает за
 * --------------------------------------------------------------------------
 * • выполнение проверки;
 * • создание сообщений;
 * • отображение сообщений;
 * • локализацию текста;
 * • сохранение результатов проверки.
 *
 * --------------------------------------------------------------------------
 * Инварианты
 * --------------------------------------------------------------------------
 * • коллекция содержит только ValidationMessage;
 * • после создания коллекция не изменяется (Immutable);
 * • методы фильтрации возвращают новую коллекцию.
 */
final class ValidationMessageCollection implements CollectionInterface, IteratorAggregate, Countable
{
	/**
	 * Коллекция сообщений проверки.
	 *
	 * @var ValidationMessage[]
	 */
	private array $messages;

	/**
	 * Создаёт коллекцию сообщений проверки.
	 *
	 * @param ValidationMessage ...$messages
	 */
	public function __construct(
		ValidationMessage ...$messages
	) {
		$this->messages = $messages;
	}

	/**
	 * Возвращает новую коллекцию
	 * с добавленным сообщением.
	 */
	public function add(
		ValidationMessage $message
	): self {

		$messages = $this->messages;

		/*
       * Добавление выполняется
       * в копию массива.
       *
       * Исходная коллекция
       * остаётся неизменяемой.
       */
		$messages[] = $message;

		return new self(
			...$messages
		);
	}

	/**
	 * Добавляет сообщение об ошибке.
	 *
	 * @param ValidationMessageCode $code
	 * @param string                $message
	 */
	public function addError(
		ValidationMessageCode $code,
		string $message
	): self {
		return $this->add(
			new ValidationMessage(
				ValidationMessageLevel::ERROR,
				$code,
				$message
			)
		);
	}

	/**
	 * Добавляет предупреждение.
	 *
	 * @param ValidationMessageCode $code
	 * @param string                $message
	 */
	public function addWarning(
		ValidationMessageCode $code,
		string $message
	): self {
		return $this->add(
			new ValidationMessage(
				ValidationMessageLevel::WARNING,
				$code,
				$message
			)
		);
	}

	/**
	 * Добавляет информационное сообщение.
	 *
	 * @param ValidationMessageCode $code
	 * @param string                $message
	 */
	public function addInfo(
		ValidationMessageCode $code,
		string $message
	): self {
		return $this->add(
			new ValidationMessage(
				ValidationMessageLevel::INFO,
				$code,
				$message
			)
		);
	}

	/**
	 * Возвращает коллекцию ошибок.
	 */
	public function errors(): self
	{
		return $this->filter(
			static fn(ValidationMessage $message): bool =>
			$message->isError()
		);
	}

	/**
	 * Возвращает коллекцию предупреждений.
	 */
	public function warnings(): self
	{
		return $this->filter(
			static fn(ValidationMessage $message): bool =>
			$message->isWarning()
		);
	}

	/**
	 * Возвращает информационные сообщения.
	 */
	public function info(): self
	{
		return $this->filter(
			static fn(ValidationMessage $message): bool =>
			$message->isInfo()
		);
	}

	/**
	 * Возвращает новую коллекцию, содержащую только элементы,
	 * удовлетворяющие условию.
	 *
	 * @param Closure $callback Функция фильтрации.
	 */
	private function filter(
		Closure $callback
	): self {
		return new self(
			...array_values(
				array_filter(
					$this->messages,
					$callback
				)
			)
		);
	}

	/**
	 * Возвращает все сообщения.
	 *
	 * @return ValidationMessage[]
	 */
	public function all(): array
	{
		return $this->messages;
	}

	/**
	 * Проверяет, пуста ли коллекция.
	 */
	public function isEmpty(): bool
	{
		return $this->count() === 0;
	}

	/**
	 * Возвращает количество сообщений.
	 */
	public function count(): int
	{
		return count($this->messages);
	}

	/**
	 * Возвращает итератор коллекции.
	 */
	public function getIterator(): Traversable
	{
		return new ArrayIterator($this->messages);
	}
}
