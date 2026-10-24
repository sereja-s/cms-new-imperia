<?php

declare(strict_types=1);

namespace Imperia\Modules\ProductNotice\Hooks;

use Imperia\Modules\ProductNotice\Views\ProductNoticeRenderer;

/**
 * Регистрирует вывод уведомления на странице отдельного товара.
 *
 * Используется стандартный хук WooCommerce:
 * woocommerce_after_single_product_summary
 *
 * В стандартной разметке WooCommerce:
 * - вкладки с описанием товара выводятся с приоритетом 10;
 * - похожие товары обычно выводятся с приоритетом 20.
 *
 * Приоритет 19 помещает уведомление между этими блоками.
 */
final class ProductNotice
{
	/**
	 * Объект, формирующий HTML уведомления.
	 */
	private ProductNoticeRenderer $renderer;

	/**
	 * Передаёт рендерер в обработчик.
	 *
	 * @param ProductNoticeRenderer $renderer Рендерер уведомления.
	 */
	public function __construct(ProductNoticeRenderer $renderer)
	{
		$this->renderer = $renderer;
	}

	/**
	 * Регистрирует обработчик в WordPress.
	 */
	public function register(): void
	{
		/*
         * Приоритет 19 выбран намеренно:
         * стандартный блок похожих товаров WooCommerce
         * обычно подключается с приоритетом 20.
         */
		add_action(
			'woocommerce_after_single_product_summary',
			[$this, 'render'],
			19
		);
	}

	/**
	 * Выводит уведомление.
	 *
	 * Дополнительно проверяем тип страницы, чтобы уведомление
	 * не появилось в другом контексте при изменении хуков темы.
	 */
	public function render(): void
	{
		if (
			!function_exists('is_product')
			|| !is_product()
		) {
			return;
		}

		$this->renderer->render();
	}
}
