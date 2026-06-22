<?php

namespace Imperia\Modules\Contacts\Cache;


use Imperia\Modules\Contacts\Services\ContactRepository;


/**
 * ==========================================================
 * CONTACT CACHE
 * ==========================================================
 *
 * Слой кеширования модуля Contacts.
 *
 *
 * Ответственность:
 *
 * - хранение подготовленных данных;
 * - быстрый доступ;
 * - очистка кеша.
 *
 *
 * НЕ отвечает:
 *
 * - за получение данных;
 * - за фильтрацию;
 * - за вывод.
 *
 *
 * Архитектура:
 *
 *
 * ContactRepository
 *
 *        |
 *        ↓
 *
 * ContactCache
 *
 *        |
 *        ↓
 *
 * ContactManager
 *
 *
 * ==========================================================
 */


final class ContactCache
{


	/**
	 * Ключ WordPress Object Cache.
	 *
	 * Отличается от OPTION_KEY Repository.
	 *
	 */
	private const CACHE_KEY =
	'imperia_contacts_cache';



	/**
	 * Группа кеша Imperia Core.
	 */
	private const CACHE_GROUP =
	'imperia_core';




	/**
	 * Repository.
	 */
	private ContactRepository $repository;




	/**
	 * Constructor.
	 */
	public function __construct(
		ContactRepository $repository
	) {

		$this->repository =
			$repository;
	}





	/**
	 * Получить контакты.
	 *
	 *
	 * Алгоритм:
	 *
	 * 1. Проверить кеш.
	 *
	 * 2. Если найден массив —
	 *    вернуть его.
	 *
	 * 3. Иначе получить данные
	 *    через Repository.
	 *
	 * 4. Сохранить в кеш.
	 *
	 *
	 * @return array
	 */
	public function get(): array
	{


		$contacts =
			wp_cache_get(

				self::CACHE_KEY,

				self::CACHE_GROUP

			);



		/**
		 * Возвращаем только
		 * корректный массив.
		 */
		if (
			is_array($contacts)
		) {

			return $contacts;
		}





		/**
		 * Кеша нет.
		 *
		 * Получаем данные
		 * из Repository.
		 */
		$contacts =
			$this->repository->getAll();





		/**
		 * Записываем подготовленные данные.
		 */
		wp_cache_set(

			self::CACHE_KEY,

			$contacts,

			self::CACHE_GROUP

		);



		return $contacts;
	}





	/**
	 * Очистить кеш.
	 *
	 *
	 * Вызывается после:
	 *
	 * - изменения настроек;
	 * - добавления;
	 * - удаления.
	 *
	 */
	public function clear(): void
	{


		wp_cache_delete(

			self::CACHE_KEY,

			self::CACHE_GROUP

		);
	}





	/**
	 * Пересобрать кеш.
	 *
	 *
	 * Используется:
	 *
	 * - импорт;
	 * - массовое обновление.
	 *
	 */
	public function refresh(): array
	{


		$this->clear();



		return $this->get();
	}
}
