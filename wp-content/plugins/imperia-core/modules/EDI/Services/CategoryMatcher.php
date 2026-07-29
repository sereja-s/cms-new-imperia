<?php

namespace Imperia\Modules\EDI\Services;

use BytePerfect\EDI\Utils;

/**
 * ==========================================================
 * CategoryMatcher
 * ==========================================================
 *
 * Отвечает только за сопоставление
 * категорий 1С с категориями WooCommerce.
 *
 * Сам XML он не читает.
 *
 * Получает:
 *
 *      GUID
 *      название
 *
 * Возвращает
 *
 *      term_id
 *
 */
final class CategoryMatcher
{

	/**
	 * Ключ таблицы соответствий EDI.
	 */
	private const MAP_KEY = '_edi_1c_category_map';

	/**
	 * Найти категорию.
	 */
	public function match(
		string $guid,
		string $name
	): ?int {

		/**
		 * Сначала ищем по GUID.
		 */

		$categoryId = Utils::get_guid_id_match(
			self::MAP_KEY,
			$guid
		);

		/**
		 * Проверяем,
		 * существует ли категория.
		 */

		if (
			$categoryId &&
			get_term_by(
				'id',
				$categoryId,
				'product_cat'
			)
		) {

			return (int) $categoryId;
		}

		/**
		 * GUID неизвестен.
		 *
		 * Ищем по названию.
		 */

		$term = get_term_by(
			'name',
			$name,
			'product_cat'
		);

		if (!$term) {
			return null;
		}

		/**
		 * Запоминаем соответствие.
		 */

		Utils::set_guid_id_match(
			self::MAP_KEY,
			$guid,
			(int)$term->term_id
		);

		return (int)$term->term_id;
	}
}
