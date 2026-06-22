<?php

namespace Imperia\Modules\Contacts\Hooks;


use Imperia\Modules\Contacts\Services\ContactManager;

use Imperia\Modules\Contacts\Views\FloatingContactsRenderer;


/**
 * ==========================================================
 * FLOATING CONTACTS HOOK
 * ==========================================================
 *
 * Подключение плавающих контактов
 * к WordPress.
 *
 *
 * Ответственность:
 *
 * - регистрация WordPress hook;
 * - получение данных через ContactManager;
 * - передача данных Renderer.
 *
 *
 * НЕ отвечает:
 *
 * - за HTML;
 * - за CSS;
 * - за получение данных из БД.
 *
 *
 * ==========================================================
 */


final class FloatingContacts
{


	/**
	 * Главный сервис контактов.
	 */
	private ContactManager $contacts;



	/**
	 * Renderer.
	 */
	private FloatingContactsRenderer $renderer;





	/**
	 * Конструктор.
	 */
	public function __construct(

		ContactManager $contacts,

		FloatingContactsRenderer $renderer

	) {

		$this->contacts = $contacts;

		$this->renderer = $renderer;
	}





	/**
	 * Регистрация WordPress hook.
	 *
	 */
	public function register(): void
	{


		add_action(

			'wp_footer',

			[
				$this,
				'render'
			]

		);
	}





	/**
	 * Вывод блока.
	 *
	 */
	public function render(): void
	{


		/**
		 * Получаем контакты
		 * для размещения floating.
		 */
		$contacts =
			$this->contacts->for(
				'floating'
			);



		/**
		 * Если контактов нет,
		 * ничего не выводим.
		 */
		if (
			empty($contacts)
		) {
			return;
		}





		/**
		 * Передаем данные
		 * в Renderer.
		 */
		echo $this->renderer->render(
			$contacts
		);
	}
}
