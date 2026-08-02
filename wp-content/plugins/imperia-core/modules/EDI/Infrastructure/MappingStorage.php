<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Infrastructure;

use BytePerfect\EDI\Utils;

/**
 * ==========================================================
 * MappingStorage
 * ==========================================================
 *
 * Универсальная обёртка над механизмом хранения
 * соответствий (mapping), используемым плагином EDI.
 *
 * ----------------------------------------------------------
 * Зачем нужен этот класс
 * ----------------------------------------------------------
 *
 * Плагин EDI уже умеет:
 *
 *  • искать соответствие GUID → ID;
 *  • сохранять соответствие GUID → ID.
 *
 * Мы НЕ переписываем эту функциональность.
 *
 * Вместо этого используем штатные методы EDI:
 *
 *      Utils::get_guid_id_match()
 *      Utils::set_guid_id_match()
 *
 * Единственное, чего нет в EDI —
 * удаление устаревшего соответствия.
 *
 * Именно поэтому данный класс содержит
 * только небольшую обёртку над существующим
 * функционалом и реализует недостающий метод
 * forget().
 *
 * Благодаря этому:
 *
 * ✔ сохраняется совместимость с EDI;
 * ✔ отсутствует дублирование кода;
 * ✔ все Matcher работают одинаково.
 */
final class MappingStorage
{
	/**
	 * ======================================================
	 * Найти WooCommerce ID по GUID.
	 * ======================================================
	 *
	 * Используется штатная функция EDI.
	 *
	 * @return int|null
	 */
	public function find(
		string $mapKey,
		string $guid
	): ?int {

		try {

			$id = Utils::get_guid_id_match(
				$mapKey,
				$guid
			);

			return $id !== null
				? (int) $id
				: null;
		} catch (\Throwable $e) {

			imperia_log(
				sprintf(
					'ERROR: Cannot read mapping. Key=%s GUID=%s. %s',
					$mapKey,
					$guid,
					$e->getMessage()
				)
			);

			throw $e;
		}
	}

	/**
	 * ======================================================
	 * Сохранить соответствие GUID ↔ ID.
	 * ======================================================
	 *
	 * Используется штатная функция EDI.
	 */
	public function remember(
		string $mapKey,
		string $guid,
		int $id
	): void {

		try {

			Utils::set_guid_id_match(
				$mapKey,
				$guid,
				$id
			);
		} catch (\Throwable $e) {

			imperia_log(
				sprintf(
					'ERROR: Cannot save mapping. Key=%s GUID=%s ID=%d. %s',
					$mapKey,
					$guid,
					$id,
					$e->getMessage()
				)
			);

			throw $e;
		}
	}

	/**
	 * ======================================================
	 * Удалить соответствие GUID ↔ ID.
	 * ======================================================
	 *
	 * В оригинальном EDI такой функции нет.
	 *
	 * Поэтому реализуем её самостоятельно.
	 *
	 * После удаления категории WooCommerce
	 * импорт сможет снова выполнить поиск
	 * категории по названию.
	 */
	public function forget(
		string $mapKey,
		string $guid
	): void {

		try {

			$map = get_option(
				$mapKey,
				[]
			);

			if (!is_array($map)) {
				return;
			}

			if (!array_key_exists($guid, $map)) {
				return;
			}

			unset($map[$guid]);

			update_option(
				$mapKey,
				$map,
				false
			);
		} catch (\Throwable $e) {

			imperia_log(
				sprintf(
					'ERROR: Cannot remove mapping. Key=%s GUID=%s. %s',
					$mapKey,
					$guid,
					$e->getMessage()
				)
			);

			throw $e;
		}
	}
}
