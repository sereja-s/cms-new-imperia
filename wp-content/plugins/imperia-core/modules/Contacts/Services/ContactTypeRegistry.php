<?php

namespace Imperia\Modules\Contacts\Services;


/**
 * ==========================================================
 * CONTACT TYPE REGISTRY
 * ==========================================================
 *
 * Реестр типов контактов.
 *
 * Получает данные из Config.
 *
 * Сам типы не хранит.
 *
 * ==========================================================
 */


final class ContactTypeRegistry
{


	/**
	 * Список типов.
	 */
	private array $types = [];





	/**
	 * Конструктор.
	 *
	 * Загружает конфигурацию.
	 */
	public function __construct()
	{


		$config = require __DIR__
			. '/../Config/contact-types.php';



		$this->types = $config;
	}





	/**
	 * Получить все типы.
	 */
	public function all(): array
	{

		return $this->types;
	}





	/**
	 * Проверить существование типа.
	 */
	public function exists(
		string $type
	): bool {


		return in_array(

			$type,

			$this->types,

			true

		);
	}





	/**
	 * Зарегистрировать новый тип.
	 *
	 *
	 * Например:
	 *
	 * $registry->register('whatsapp');
	 *
	 */
	public function register(
		string $type
	): void {


		if (
			!$this->exists($type)
		) {

			$this->types[] = $type;
		}
	}
}
