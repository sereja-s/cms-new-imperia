<?php

namespace Imperia\Modules\Contacts\Services;


/**
 * ==========================================================
 * CONTACT REPOSITORY
 * ==========================================================
 *
 * Слой доступа к данным модуля Contacts.
 *
 *
 * Ответственность:
 *
 * - получение контактов;
 * - сохранение контактов;
 * - обновление контактов;
 * - удаление контактов;
 * - первичная установка данных.
 *
 *
 * Repository работает только
 * с источником хранения.
 *
 *
 * Источник:
 *
 * WordPress wp_options
 *
 *
 * option_name:
 *
 * imperia_contacts
 *
 *
 *
 * ВАЖНО:
 *
 * Repository НЕ отвечает за:
 *
 * - кеширование;
 * - выбор контактов;
 * - размещение;
 * - HTML;
 * - WordPress hooks.
 *
 *
 * Архитектура:
 *
 *
 * Repository
 *
 *      ↓
 *
 * Cache
 *
 *      ↓
 *
 * Manager
 *
 *      ↓
 *
 * Renderer
 *
 *
 * ==========================================================
 */


final class ContactRepository
{


	/**
	 * Ключ хранения контактов.
	 *
	 *
	 * В базе WordPress:
	 *
	 * wp_options
	 *
	 *
	 * option_name:
	 *
	 * imperia_contacts
	 *
	 */
	private const OPTION_KEY =
	'imperia_contacts';





	/**
	 * Получить все контакты.
	 *
	 *
	 * Возвращает сырые данные.
	 *
	 * Здесь нет:
	 *
	 * - фильтрации;
	 * - сортировки;
	 * - проверки размещения.
	 *
	 *
	 * @return array
	 */
	public function getAll(): array
	{


		$contacts =
			get_option(

				self::OPTION_KEY,

				[]

			);



		/**
		 * Защита от поврежденных данных.
		 *
		 * Если вместо массива
		 * получили другое значение,
		 * возвращаем пустой массив.
		 */
		if (
			!is_array($contacts)
		) {

			return [];
		}



		return $contacts;
	}





	/**
	 * Получить контакт по ID.
	 *
	 *
	 * Например:
	 *
	 * phone_main
	 *
	 *
	 * @param string $id
	 *
	 * @return array|null
	 */
	public function find(
		string $id
	): ?array {


		$contacts =
			$this->getAll();



		foreach (
			$contacts as $contact
		) {


			if (
				isset($contact['id'])
				&&
				$contact['id'] === $id
			) {

				return $contact;
			}
		}



		return null;
	}





	/**
	 * Сохранить все контакты.
	 *
	 *
	 * Используется:
	 *
	 * - Admin;
	 * - SettingsSaver.
	 *
	 *
	 * После изменения данных
	 * внешний слой должен очистить Cache.
	 *
	 *
	 * @param array $contacts
	 *
	 * @return bool
	 */
	public function save(
		array $contacts
	): bool {


		return update_option(

			self::OPTION_KEY,

			$contacts

		);
	}





	/**
	 * Добавить новый контакт.
	 *
	 *
	 * @param array $contact
	 *
	 * @return bool
	 */
	public function add(
		array $contact
	): bool {


		$contacts =
			$this->getAll();



		$contacts[] =
			$contact;



		return $this->save(
			$contacts
		);
	}





	/**
	 * Обновить контакт.
	 *
	 *
	 * Поиск:
	 *
	 * contact[id]
	 *
	 *
	 * @param string $id
	 * @param array $data
	 *
	 * @return bool
	 */
	public function update(
		string $id,
		array $data
	): bool {


		$contacts =
			$this->getAll();



		foreach (
			$contacts as $key => $contact
		) {


			if (
				isset($contact['id'])
				&&
				$contact['id'] === $id
			) {


				$contacts[$key] =
					array_merge(

						$contact,

						$data

					);



				return $this->save(
					$contacts
				);
			}
		}



		return false;
	}





	/**
	 * Удалить контакт.
	 *
	 *
	 * @param string $id
	 *
	 * @return bool
	 */
	public function delete(
		string $id
	): bool {


		$contacts =
			$this->getAll();



		$contacts =
			array_filter(

				$contacts,

				function ($contact) use ($id) {


					return !(
						isset($contact['id'])
						&&
						$contact['id'] === $id
					);
				}

			);



		/**
		 * Сбрасываем ключи массива.
		 */
		$contacts =
			array_values(
				$contacts
			);



		return $this->save(
			$contacts
		);
	}





	/**
	 * Проверка существования данных.
	 *
	 *
	 * @return bool
	 */
	public function exists(): bool
	{


		return get_option(

			self::OPTION_KEY,

			false

		) !== false;
	}





	/**
	 * ======================================================
	 * INITIALIZE DEFAULTS
	 * ======================================================
	 *
	 * Первичная установка
	 * стандартных контактов.
	 *
	 *
	 * Используется:
	 *
	 * - первый запуск модуля;
	 * - установка плагина.
	 *
	 *
	 * НЕ используется:
	 *
	 * - при каждом запросе;
	 * - при выводе сайта.
	 *
	 *
	 * В будущем сюда может прийти
	 * отдельный ModuleInstaller.
	 *
	 * ======================================================
	 */
	public function initializeDefaults(): void
	{


		/**
		 * Проверяем наличие option.
		 *
		 *
		 * Если:
		 *
		 * imperia_contacts
		 *
		 * уже существует,
		 *
		 * ничего не делаем.
		 */
		if (
			get_option(

				self::OPTION_KEY,

				false

			) !== false
		) {

			return;
		}



		/**
		 * Загружаем
		 * стандартные контакты.
		 *
		 * Источник:
		 *
		 * Config/contact-defaults.php
		 */
		$defaults =
			require __DIR__
			. '/../Config/contact-defaults.php';



		/**
		 * Первичная запись.
		 */
		update_option(

			self::OPTION_KEY,

			$defaults

		);
	}

	/**
	 * ======================================================
	 * RESET CONTACTS
	 * ======================================================
	 *
	 * Полностью удаляет сохранённые контакты
	 * из базы данных и повторно загружает
	 * значения по умолчанию.
	 *
	 * Используется только:
	 *
	 * - во время разработки;
	 * - при миграциях структуры данных;
	 * - при обновлении contacts-defaults.php.
	 *
	 * После вызова метода:
	 *
	 * contacts-defaults.php
	 *         ↓
	 * initializeDefaults()
	 *         ↓
	 * wp_options
	 *
	 * ======================================================
	 */
	public function reset(): void
	{
		delete_option(
			self::OPTION_KEY
		);

		$this->initializeDefaults();
	}
}
