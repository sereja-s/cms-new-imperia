<?php

namespace Imperia\Modules\Contacts\Services;


use Imperia\Modules\Contacts\Cache\ContactCache;


/**
 * ==========================================================
 * CONTACT MANAGER
 * ==========================================================
 *
 * Главный фасад модуля Contacts.
 *
 *
 * Единственная публичная точка доступа
 * к данным модуля.
 *
 *
 * Внешний код НЕ должен работать напрямую с:
 *
 * - Repository
 * - Cache
 * - Registry
 * - CollectionManager
 *
 *
 * Все обращения идут через:
 *
 * $contacts
 *
 *
 * Примеры:
 *
 * $contacts->for('floating');
 *
 * $contacts->type('telegram');
 *
 * $contacts->collection('socials');
 *
 *
 * ==========================================================
 */


final class ContactManager
{


	/**
	 * Кеш контактов.
	 */
	private ContactCache $cache;



	/**
	 * Менеджер коллекций.
	 */
	private ContactCollectionManager $collections;



	/**
	 * Реестр размещений.
	 */
	private ContactPlacementRegistry $placements;



	/**
	 * Реестр типов контактов.
	 */
	private ContactTypeRegistry $types;



	/**
	 * Реестр коллекций.
	 */
	private ContactCollectionRegistry $collectionsRegistry;





	/**
	 * ======================================================
	 * CONSTRUCTOR
	 * ======================================================
	 */
	public function __construct(

		ContactCache $cache,

		ContactCollectionManager $collections,

		ContactPlacementRegistry $placements,

		ContactTypeRegistry $types,

		ContactCollectionRegistry $collectionsRegistry

	) {


		$this->cache =
			$cache;


		$this->collections =
			$collections;


		$this->placements =
			$placements;


		$this->types =
			$types;


		$this->collectionsRegistry =
			$collectionsRegistry;
	}





	/**
	 * ======================================================
	 * FOR
	 * ======================================================
	 *
	 * Получить контакты
	 * для места размещения.
	 *
	 *
	 * Пример:
	 *
	 * $contacts->for('floating');
	 *
	 *
	 * ======================================================
	 */
	public function for(
		string $placement
	): array {


		/**
		 * Проверяем существование
		 * такого размещения.
		 */
		if (
			!$this->placements->exists($placement)
		) {

			return [];
		}



		$contacts =
			$this->cache->get();




		return array_values(

			array_filter(

				$contacts,

				function (array $contact) use ($placement) {


					/**
					 * Неактивные контакты
					 * не выводим.
					 */
					if (
						empty($contact['active'])
					) {

						return false;
					}



					/**
					 * Проверяем наличие
					 * списка размещений.
					 */
					if (
						empty($contact['placements'])
						||
						!is_array(
							$contact['placements']
						)
					) {

						return false;
					}



					return in_array(

						$placement,

						$contact['placements'],

						true

					);
				}

			)

		);
	}





	/**
	 * ======================================================
	 * TYPE
	 * ======================================================
	 *
	 * Получить контакты
	 * определенного типа.
	 *
	 *
	 * Пример:
	 *
	 * $contacts->type('telegram');
	 *
	 * ======================================================
	 */
	public function type(
		string $type
	): array {


		if (
			!$this->types->exists($type)
		) {

			return [];
		}



		return $this->collections->byType(
			$type
		);
	}





	/**
	 * ======================================================
	 * PLACEMENT
	 * ======================================================
	 *
	 * Проверить наличие
	 * места размещения.
	 *
	 *
	 * Пример:
	 *
	 * $contacts->placement('footer');
	 *
	 * ======================================================
	 */
	public function placement(
		string $placement
	): bool {


		return $this->placements->exists(
			$placement
		);
	}





	/**
	 * ======================================================
	 * ALL
	 * ======================================================
	 *
	 * Получить все контакты.
	 *
	 * ======================================================
	 */
	public function all(): array
	{

		return $this->cache->get();
	}





	/**
	 * ======================================================
	 * COLLECTION API
	 * ======================================================
	 */





	/**
	 * Получить одну коллекцию.
	 *
	 *
	 * Например:
	 *
	 * $contacts->collection('socials');
	 *
	 *
	 * Вернет:
	 *
	 * Telegram
	 * MAX
	 * VK
	 *
	 */
	public function collection(
		string $collection
	): array {

		return $this->collections->byCollection(
			$collection
		);
	}





	/**
	 * Получить несколько коллекций.
	 *
	 *
	 * Например:
	 *
	 * Footer:
	 *
	 * phones
	 * emails
	 * socials
	 *
	 */
	public function collectionGroup(
		array $collections
	): array {

		return $this->collections->multiple(
			$collections
		);
	}





	/**
	 * Быстрые методы коллекций.
	 *
	 * Для удобства.
	 */


	public function phones(): array
	{

		return $this->collections->phones();
	}




	public function emails(): array
	{

		return $this->collections->emails();
	}




	public function socials(): array
	{

		return $this->collections->socials();
	}




	public function addresses(): array
	{

		return $this->collections->addresses();
	}





	/**
	 * ======================================================
	 * REGISTRY API
	 * ======================================================
	 */





	/**
	 * Получить доступные типы контактов.
	 *
	 *
	 * Используется:
	 *
	 * Admin
	 * Extensions
	 *
	 */
	public function types(): array
	{

		return $this->types->all();
	}





	/**
	 * Получить доступные места вывода.
	 *
	 */
	public function placements(): array
	{

		return $this->placements->all();
	}





	/**
	 * Получить доступные коллекции.
	 *
	 *
	 * Например:
	 *
	 * phones
	 * socials
	 * emails
	 *
	 */
	public function availableCollections(): array
	{

		return $this->collectionsRegistry->all();
	}
}
