<?php

namespace Imperia\Modules\Contacts\Services;


/**
 * ==========================================================
 * CONTACT COLLECTION REGISTRY
 * ==========================================================
 *
 * Реестр коллекций контактов.
 *
 *
 * Назначение:
 *
 * Управление доступными коллекциями
 * модуля Contacts.
 *
 *
 * Источник данных:
 *
 * Config/contact-collections.php
 *
 *
 * Примеры коллекций:
 *
 * phones
 *
 * emails
 *
 * socials
 *
 * addresses
 *
 *
 *
 * ВАЖНО:
 *
 * Registry не работает
 * с самими контактами.
 *
 *
 * Он хранит только описание
 * доступных коллекций.
 *
 *
 * Архитектура:
 *
 *
 * Config
 *
 *    |
 *    ↓
 *
 * CollectionRegistry
 *
 *    |
 *    ↓
 *
 * CollectionManager
 *
 *
 * ==========================================================
 */


final class ContactCollectionRegistry
{


	/**
	 * Зарегистрированные коллекции.
	 *
	 * Пример:
	 *
	 * [
	 *
	 *   'phones'=>[
	 *       'title'=>'Телефоны'
	 *   ]
	 *
	 * ]
	 *
	 */
	private array $collections;




	/**
	 * Constructor.
	 *
	 * Загружает конфигурацию коллекций.
	 */
	public function __construct()
	{


		$this->collections =
			require __DIR__
			. '/../Config/contact-collections.php';
	}





	/**
	 * Получить все коллекции.
	 *
	 *
	 * Используется:
	 *
	 * - Admin;
	 * - Manager;
	 * - будущие интеграции.
	 *
	 *
	 * @return array
	 */
	public function all(): array
	{

		return $this->collections;
	}





	/**
	 * Проверить существование коллекции.
	 *
	 *
	 * Например:
	 *
	 * socials
	 *
	 *
	 * @param string $collection
	 *
	 * @return bool
	 */
	public function exists(
		string $collection
	): bool {


		return isset(
			$this->collections[$collection]
		);
	}





	/**
	 * Получить описание коллекции.
	 *
	 *
	 * Например:
	 *
	 * [
	 *
	 *   'title'=>'Социальные сети'
	 *
	 * ]
	 *
	 *
	 * @param string $collection
	 *
	 * @return array|null
	 */
	public function get(
		string $collection
	): ?array {


		if (
			!$this->exists($collection)
		) {

			return null;
		}



		return $this->collections[$collection];
	}





	/**
	 * Получить список ID коллекций.
	 *
	 *
	 * Возвращает:
	 *
	 * [
	 *
	 *   phones,
	 *   emails,
	 *   socials
	 *
	 * ]
	 *
	 *
	 * @return array
	 */
	public function keys(): array
	{


		return array_keys(
			$this->collections
		);
	}
}
