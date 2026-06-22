<?php

namespace Imperia\Modules\Contacts;

use Imperia\Core\ModuleInterface;

use Imperia\Modules\Contacts\Services\ContactRepository;
use Imperia\Modules\Contacts\Cache\ContactCache;

use Imperia\Modules\Contacts\Services\ContactManager;
use Imperia\Modules\Contacts\Services\ContactTypeRegistry;
use Imperia\Modules\Contacts\Services\ContactPlacementRegistry;
use Imperia\Modules\Contacts\Services\ContactCollectionRegistry;
use Imperia\Modules\Contacts\Services\ContactCollectionManager;

use Imperia\Modules\Contacts\Views\FloatingContactsRenderer;

use Imperia\Modules\Contacts\Hooks\FloatingContacts;
use Imperia\Modules\Contacts\Hooks\AssetsLoader;
use Imperia\Modules\Contacts\Views\ContactIconRenderer;

/**
 * ==========================================================
 * MODULE: CONTACTS
 * ==========================================================
 *
 * Главный класс модуля Contacts.
 *
 * Точка входа для ModuleManager.
 *
 * ModuleManager знает только:
 *
 * 1. Создать модуль.
 * 2. Вызвать init().
 *
 * После этого управление полностью
 * передается модулю Contacts.
 *
 * ==========================================================
 *
 * Задача Module:
 *
 * - собрать зависимости;
 * - зарегистрировать Hooks;
 * - зарегистрировать Renderers;
 * - выполнить первоначальную инициализацию.
 *
 * ==========================================================
 *
 * Здесь НЕ должно быть:
 *
 * - HTML;
 * - SQL;
 * - фильтрации контактов;
 * - бизнес-логики;
 * - работы с WordPress Options.
 *
 * ==========================================================
 */
final class Module implements ModuleInterface
{
	/**
	 * ======================================================
	 * MODULE INITIALIZATION
	 * ======================================================
	 */
	public function init(): void
	{
		/**
		 * ==================================================
		 * REPOSITORY
		 * ==================================================
		 */

		$repository =
			new ContactRepository();

		/**
		 * Временно обновляем
		 * данные по умолчанию (удалить после первого запуска сайта после обновления даннх контактов по умолчанию)
		 */
		/* $repository->reset();

		imperia_log(
			'Кеш данных модуля Контактов очищен'
		); */

		/**
		 * Первичная установка контактов.
		 *
		 * Выполняется только один раз.
		 */
		$repository->initializeDefaults();



		/**
		 * ==================================================
		 * CACHE
		 * ==================================================
		 */

		$cache =
			new ContactCache(
				$repository
			);



		/**
		 * ==================================================
		 * REGISTRIES
		 * ==================================================
		 */

		$types =
			new ContactTypeRegistry();

		$placements =
			new ContactPlacementRegistry();

		$collectionsRegistry =
			new ContactCollectionRegistry();



		/**
		 * ==================================================
		 * COLLECTION MANAGER
		 * ==================================================
		 */

		$collectionManager =
			new ContactCollectionManager(

				$cache,

				$collectionsRegistry

			);



		/**
		 * ==================================================
		 * MAIN FACADE
		 * ==================================================
		 */

		$contacts =
			new ContactManager(

				$cache,

				$collectionManager,

				$placements,

				$types,

				$collectionsRegistry

			);



		/**
		 * ==================================================
		 * RENDERERS
		 * ==================================================
		 */

		/**
		 * SVG иконки.
		 */
		$icons =
			new ContactIconRenderer();



		/**
		 * Renderer плавающих контактов.
		 */
		$floatingRenderer =
			new FloatingContactsRenderer(
				$icons
			);



		/**
		 * ==================================================
		 * HOOKS
		 * ==================================================
		 */

		$assets =
			new AssetsLoader();

		$floatingContacts =
			new FloatingContacts(

				$contacts,

				$floatingRenderer

			);



		/**
		 * ==================================================
		 * REGISTRATION
		 * ==================================================
		 */

		$assets->register();

		$floatingContacts->register();
	}
}
