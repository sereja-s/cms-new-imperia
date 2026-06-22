<?php

namespace Imperia\Modules\Contacts\Services;


use Imperia\Modules\Contacts\Cache\ContactCache;


/**
 * ==========================================================
 * CONTACT COLLECTION MANAGER
 * ==========================================================
 *
 * Менеджер коллекций контактов.
 *
 *
 * Ответственность:
 *
 * - получение контактов из Cache;
 * - фильтрация по коллекциям;
 * - фильтрация по типам.
 *
 *
 * НЕ отвечает за:
 *
 * - хранение данных;
 * - конфигурацию коллекций;
 * - регистрацию новых коллекций.
 *
 *
 * За конфигурацию отвечает:
 *
 * ContactCollectionRegistry
 *
 *
 * Архитектура:
 *
 *
 * Config/contact-collections.php
 *
 *          |
 *          ↓
 *
 * ContactCollectionRegistry
 *
 *          |
 *          ↓
 *
 * ContactCollectionManager
 *
 *          |
 *          ↓
 *
 * ContactCache
 *
 *
 * ==========================================================
 */


final class ContactCollectionManager
{


	/**
	 * Cache слой.
	 */
	private ContactCache $cache;



	/**
	 * Registry коллекций.
	 */
	private ContactCollectionRegistry $registry;





	/**
	 * Constructor.
	 */
	public function __construct(

		ContactCache $cache,

		ContactCollectionRegistry $registry

	) {

		$this->cache =
			$cache;


		$this->registry =
			$registry;
	}





	/**
	 * ======================================================
	 * CONTACTS
	 * ======================================================
	 *
	 * Получить все контакты из Cache.
	 *
	 */
	private function contacts(): array
	{

		return $this->cache->get();
	}





	/**
	 * ======================================================
	 * ACTIVE CHECK
	 * ======================================================
	 *
	 * Проверка активности контакта.
	 *
	 * Неактивные контакты:
	 *
	 * - не выводятся;
	 * - остаются в базе;
	 * - могут быть включены позже.
	 *
	 */
	private function isActive(
		array $contact
	): bool {

		return !empty($contact['active']);
	}





	/**
	 * ======================================================
	 * EXISTS
	 * ======================================================
	 *
	 * Проверка существования коллекции.
	 *
	 */
	public function exists(
		string $collection
	): bool {

		return $this->registry->exists(
			$collection
		);
	}





	/**
	 * ======================================================
	 * ALL
	 * ======================================================
	 *
	 * Получить все контакты.
	 *
	 */
	public function all(): array
	{

		return array_values(

			array_filter(

				$this->contacts(),

				function (array $contact) {

					return $this->isActive(
						$contact
					);
				}

			)

		);
	}





	/**
	 * ======================================================
	 * PHONES
	 * ======================================================
	 */
	public function phones(): array
	{

		return $this->byCollection(
			'phones'
		);
	}





	/**
	 * ======================================================
	 * EMAILS
	 * ======================================================
	 */
	public function emails(): array
	{

		return $this->byCollection(
			'emails'
		);
	}





	/**
	 * ======================================================
	 * SOCIALS
	 * ======================================================
	 */
	public function socials(): array
	{

		return $this->byCollection(
			'socials'
		);
	}





	/**
	 * ======================================================
	 * ADDRESSES
	 * ======================================================
	 */
	public function addresses(): array
	{

		return $this->byCollection(
			'addresses'
		);
	}





	/**
	 * ======================================================
	 * BY COLLECTION
	 * ======================================================
	 *
	 * Получить контакты коллекции.
	 *
	 *
	 * Например:
	 *
	 * socials
	 *
	 * Вернет:
	 *
	 * telegram
	 * max
	 * vk
	 *
	 */
	public function byCollection(
		string $collection
	): array {


		if (
			!$this->exists($collection)
		) {

			return [];
		}



		return array_values(

			array_filter(

				$this->contacts(),

				function (array $contact) use ($collection) {


					if (
						!$this->isActive($contact)
					) {

						return false;
					}



					if (
						empty($contact['collections'])
					) {

						return false;
					}



					if (
						!is_array(
							$contact['collections']
						)
					) {

						return false;
					}



					return in_array(

						$collection,

						$contact['collections'],

						true

					);
				}

			)

		);
	}





	/**
	 * ======================================================
	 * BY TYPE
	 * ======================================================
	 *
	 * Получить контакты конкретного типа.
	 *
	 *
	 * Например:
	 *
	 * telegram
	 *
	 */
	public function byType(
		string $type
	): array {


		return array_values(

			array_filter(

				$this->contacts(),

				function (array $contact) use ($type) {


					if (
						!$this->isActive($contact)
					) {

						return false;
					}



					return isset(
						$contact['type']
					)
						&&
						$contact['type'] === $type;
				}

			)

		);
	}





	/**
	 * ======================================================
	 * MULTIPLE
	 * ======================================================
	 *
	 * Получить несколько коллекций.
	 *
	 *
	 * Например Footer:
	 *
	 * phones
	 * emails
	 * socials
	 *
	 */
	public function multiple(
		array $collections
	): array {


		$result = [];



		foreach (
			$collections as $collection
		) {

			$result =
				array_merge(

					$result,

					$this->byCollection(
						$collection
					)

				);
		}



		return array_values(

			array_unique(

				$result,

				SORT_REGULAR

			)

		);
	}





	/**
	 * ======================================================
	 * AVAILABLE COLLECTIONS
	 * ======================================================
	 *
	 * Получить зарегистрированные коллекции.
	 *
	 * Используется:
	 *
	 * - Admin;
	 * - настройки вывода;
	 *
	 */
	public function available(): array
	{

		return $this->registry->all();
	}
}
