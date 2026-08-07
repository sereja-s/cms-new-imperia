<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Hooks\Product;

use Imperia\Modules\EDI\Services\ProductOwnership;
use WC_Product;

/**
 * ==========================================================
 * ProtectManualProducts
 * ==========================================================
 *
 * Защищает товары,
 * которые ещё НЕ участвуют
 * в обмене с 1С.
 *
 * ----------------------------------------------------------
 * Зачем нужен этот Hook
 * ----------------------------------------------------------
 *
 * В оригинальном EDI алгоритм следующий:
 *
 *      1. найти товар;
 *
 *      2. заполнить новые данные;
 *
 *      3. сохранить.
 *
 * При этом совершенно не важно,
 * участвовал товар в обмене раньше
 * или нет.
 *
 * Если ProductMatcher случайно найдёт
 * товар по артикулу или названию,
 * EDI сразу начнёт им управлять.
 *
 * Для нашего проекта это недопустимо.
 *
 * Мы договорились,
 * что товар считается принадлежащим
 * обмену только после сопоставления
 * (появления GUID).
 *
 * До этого момента товаром
 * полностью управляет администратор.
 *
 * ----------------------------------------------------------
 * Что делает данный Hook
 * ----------------------------------------------------------
 *
 * Перед сохранением:
 *
 * 1. проверяет наличие GUID;
 *
 * 2. если GUID отсутствует —
 *    отменяет изменения EDI;
 *
 * 3. если GUID существует —
 *    ничего не делает.
 *
 * Благодаря этому:
 *
 * ✔ товары,
 *   созданные вручную,
 *   никогда случайно
 *   не изменяются;
 *
 * ✔ после первого сопоставления
 *   обмен начинает работать
 *   автоматически.
 *
 * ----------------------------------------------------------
 * Почему используется именно Hook
 * ----------------------------------------------------------
 *
 * Мы не изменяем ProductParser EDI.
 *
 * Используем штатную точку расширения:
 *
 *      edi_product_before_save
 *
 * что полностью соответствует
 * архитектуре Imperia Core.
 */
final class ProtectManualProducts
{
	/**
	 * Сервис определения владельца товара.
	 */
	private ProductOwnership $ownership;

	/**
	 * Конструктор.
	 */
	public function __construct()
	{
		$this->ownership = new ProductOwnership();
	}

	/**
	 * ======================================================
	 * Регистрация Hook.
	 * ======================================================
	 */
	public function register(): void
	{
		add_action(
			'edi_product_before_save',
			[$this, 'protect'],
			30,
			2
		);
	}

	/**
	 * ======================================================
	 * Защита товаров,
	 * созданных вручную.
	 * ======================================================
	 *
	 * Вызывается непосредственно
	 * перед сохранением товара.
	 *
	 * @param WC_Product $product
	 * @param array      $productData
	 */
	public function protect(
		WC_Product $product,
		array &$productData
	): void {

		imperia_log(
			sprintf(
				'ProtectManualProducts: called for product #%d',
				$product->get_id()
			)
		);

		/*
         * Новый товар,
         * которого ещё нет в базе,
         * не требует проверки.
         *
         * Для него GUID будет записан
         * сразу после создания.
         */
		if (!$product->get_id()) {
			return;
		}

		/*
         * Если товар уже принадлежит обмену,
         * разрешаем EDI продолжить работу.
         */
		if ($this->ownership->isManaged($product)) {
			return;
		}

		/*
         * --------------------------------------------------
         * Товар создан вручную.
         *
         * Отменяем изменения,
         * которые подготовил EDI.
         * --------------------------------------------------
         *
         * Для этого возвращаем значения,
         * уже существующие в WooCommerce.
         */

		$product->set_name(
			$product->get_name()
		);

		$product->set_slug(
			$product->get_slug()
		);

		$product->set_description(
			$product->get_description()
		);

		$product->set_short_description(
			$product->get_short_description()
		);

		$product->set_sku(
			$product->get_sku()
		);

		$product->set_weight(
			$product->get_weight()
		);

		$product->set_length(
			$product->get_length()
		);

		$product->set_width(
			$product->get_width()
		);

		$product->set_height(
			$product->get_height()
		);

		$product->set_category_ids(
			$product->get_category_ids()
		);

		/*
         * Диагностика.
         *
         * После завершения тестирования
         * можно удалить.
         */
		/*
        imperia_log(
            sprintf(
                'ProtectManualProducts: product #%d ignored because it has no GUID.',
                $product->get_id()
            )
        );
        */
	}
}
