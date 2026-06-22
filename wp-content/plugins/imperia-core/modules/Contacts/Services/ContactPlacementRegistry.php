<?php

namespace Imperia\Modules\Contacts\Services;


/**
 * ==========================================================
 * CONTACT PLACEMENT REGISTRY
 * ==========================================================
 *
 * Реестр мест размещения контактов.
 *
 *
 * Отвечает за управление точками вывода:
 *
 * floating
 * footer
 * header
 *
 *
 * В будущем:
 *
 * checkout
 * product
 * mobile-menu
 * popup
 *
 *
 *
 * Источник данных:
 *
 * Config/contact-placements.php
 *
 *
 * Важно:
 *
 * Service-класс не содержит
 * жестко заданных значений.
 *
 * Все данные находятся
 * в конфигурации.
 *
 *
 * ==========================================================
 */


final class ContactPlacementRegistry
{


	/**
	 * Зарегистрированные места вывода.
	 *
	 * Например:
	 *
	 * [
	 *    'floating',
	 *    'footer',
	 *    'header'
	 * ]
	 */
	private array $placements = [];





	/**
	 * Конструктор.
	 *
	 * Загружает конфигурацию
	 * contact-placements.php
	 */
	public function __construct()
	{


		/**
		 * Подключаем файл конфигурации.
		 *
		 * Он возвращает массив.
		 *
		 */
		$config = require __DIR__
			. '/../Config/contact-placements.php';



		$this->placements = $config;
	}





	/**
	 * Получить все места размещения.
	 *
	 *
	 * Пример:
	 *
	 * $registry->all();
	 *
	 *
	 * Вернет:
	 *
	 * [
	 *    floating,
	 *    footer,
	 *    header
	 * ]
	 *
	 *
	 * @return array
	 */
	public function all(): array
	{

		return $this->placements;
	}





	/**
	 * Проверить существование размещения.
	 *
	 *
	 * Например:
	 *
	 * exists('footer')
	 *
	 * true
	 *
	 *
	 * exists('popup')
	 *
	 * false
	 *
	 *
	 * @param string $placement
	 *
	 * @return bool
	 */
	public function exists(
		string $placement
	): bool {


		return in_array(

			$placement,

			$this->placements,

			true

		);
	}





	/**
	 * Добавить новое место размещения.
	 *
	 *
	 * Например:
	 *
	 * register('popup');
	 *
	 *
	 * Теперь модуль знает:
	 *
	 * popup
	 *
	 *
	 * @param string $placement
	 *
	 * @return void
	 */
	public function register(
		string $placement
	): void {


		if (
			!$this->exists($placement)
		) {

			$this->placements[] =
				$placement;
		}
	}





	/**
	 * Удалить место размещения.
	 *
	 *
	 * Например:
	 *
	 * unregister('header');
	 *
	 *
	 * @param string $placement
	 *
	 * @return void
	 */
	public function unregister(
		string $placement
	): void {


		$this->placements =
			array_values(

				array_filter(

					$this->placements,

					function ($item) use ($placement) {

						return $item !== $placement;
					}

				)

			);
	}
}
