<?php

declare(strict_types=1);

namespace Imperia\Modules\ProductNotice;

use Imperia\Core\ModuleInterface;
use Imperia\Modules\ProductNotice\Hooks\AssetsLoader;
use Imperia\Modules\ProductNotice\Hooks\ProductNotice;
use Imperia\Modules\ProductNotice\Views\ProductNoticeRenderer;
use Throwable;

/**
 * Модуль вывода важной информации на странице отдельного товара.
 *
 * Ответственность класса:
 * - зарегистрировать инициализацию модуля;
 * - убедиться, что инициализация не выполняется повторно;
 * - создать компоненты отображения уведомления;
 * - подключить обработчики WooCommerce и загрузки CSS.
 *
 * Сам HTML уведомления находится в ProductNoticeRenderer,
 * CSS — в Assets/product-notice.css.
 */
final class Module implements ModuleInterface
{
	/**
	 * Защита от повторной инициализации.
	 *
	 * Например, если метод init() по ошибке будет вызван несколько раз,
	 * компоненты модуля не зарегистрируются повторно.
	 */
	private bool $initializationAttempted = false;

	/**
	 * Регистрирует отложенную инициализацию модуля.
	 *
	 * Хук wp срабатывает после разбора запроса WordPress.
	 * На этом этапе условие is_product() уже можно использовать
	 * для определения страницы отдельного товара WooCommerce.
	 *
	 * Метод не подключает файлы напрямую и не выводит HTML.
	 */
	public function init(): void
	{
		add_action('wp', [$this, 'initProductNotice']);
	}

	/**
	 * Инициализирует уведомление только на странице отдельного товара.
	 *
	 * Отдельный метод позволяет не создавать компоненты модуля
	 * на страницах каталога, корзины, оформления заказа и прочих страницах.
	 */
	public function initProductNotice(): void
	{
		// Не допускаем повторной инициализации.
		if ($this->initializationAttempted) {
			return;
		}

		$this->initializationAttempted = true;

		// Проверяем доступность WooCommerce и контекст страницы.
		if (
			!function_exists('is_product')
			|| !is_product()
		) {
			return;
		}

		try {
			/*
             * Создаём объект, отвечающий за HTML-разметку уведомления.
             * Передаём его обработчику WooCommerce-хука.
             */
			$renderer = new ProductNoticeRenderer();

			/*
             * Регистрируем вывод уведомления перед похожими товарами.
             */
			$productNotice = new ProductNotice($renderer);
			$productNotice->register();

			/*
             * Регистрируем подключение CSS.
             * Сам файл стилей будет добавлен в очередь только
             * на странице отдельного товара.
             */
			$assetsLoader = new AssetsLoader();
			$assetsLoader->register();
		} catch (Throwable $exception) {
			/*
             * Ошибка модуля не должна намеренно прерывать работу сайта.
             *
             * Если в Imperia Core предусмотрена функция imperia_log(),
             * записываем исключение в существующую систему логирования.
             * В противном случае ничего не выводим посетителю.
             */
			if (function_exists('imperia_log')) {
				imperia_log(
					sprintf(
						'ProductNotice: ошибка инициализации модуля: %s',
						$exception->getMessage()
					)
				);
			}
		}
	}
}
