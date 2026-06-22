<?php


/**
 * ==========================================================
 * CONTACT DEFAULTS CONFIGURATION
 * ==========================================================
 *
 * Начальные данные модуля Contacts.
 *
 *
 * Используется только при первом запуске.
 *
 *
 * После сохранения данные находятся:
 *
 * wp_options
 *
 * option_name:
 *
 * imperia_contacts
 *
 *
 * ==========================================================
 */


return [


	/**
	 * Основной телефон.
	 */
	[
		'id' => 'phone_main',

		'type' => 'phone',

		'title' => 'Телефон',

		'value' => 'tel:+79495137733',

		'icon' => 'phone',


		/**
		 * Коллекции:
		 *
		 * phones
		 */
		'collections' => [

			'phones'

		],


		/**
		 * Где показывать.
		 */
		'placements' => [

			'floating',

			'footer',

		],


		'active' => true,

	],





	/**
	 * Telegram.
	 */
	[
		'id' => 'telegram_main',

		'type' => 'telegram',

		'title' => 'Telegram',

		'value' => 'https://t.me/imperia_pola25',

		'icon' => 'telegram',


		'collections' => [

			'socials'

		],


		'placements' => [

			'floating',

			'footer',

		],


		'active' => true,

	],





	/**
	 * MAX.
	 */
	[
		'id' => 'max_main',

		'type' => 'max',

		'title' => 'MAX',

		'value' => 'https://max.ru/join/4aFxvoPbA9ifqWInwx2EN18ExIsrgOq_Q-mkuSQvH-Y',

		'icon' => 'max',


		'collections' => [

			'socials'

		],


		'placements' => [

			'floating',

			'footer',

		],


		'active' => true,

	],


];
