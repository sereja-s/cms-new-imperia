<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Services;

use Imperia\Modules\EDI\Infrastructure\MappingKeys;
use Imperia\Modules\EDI\Infrastructure\MappingStorage;

/**
 * ==========================================================
 * CategoryImportService
 * ==========================================================
 *
 * Координатор импорта категорий из CommerceML.
 *
 * ----------------------------------------------------------
 * Назначение
 * ----------------------------------------------------------
 *
 * Данный класс НЕ занимается:
 *
 * • чтением XML;
 * • поиском GUID;
 * • созданием slug;
 * • сохранением GUID;
 * • поиском категории по названию.
 *
 * Всё это выполняют специализированные сервисы.
 *
 * CategoryImportService лишь управляет процессом
 * импорта категории.
 *
 * Благодаря этому бизнес-логика не размазывается
 * между несколькими классами.
 *
 * ----------------------------------------------------------
 * Алгоритм работы
 * ----------------------------------------------------------
 *
 * 1. Получаем GUID и название категории.
 *
 * 2. Просим CategoryMatcher найти категорию.
 *
 * 3. Если категория найдена —
 *    импорт завершён.
 *
 * 4. Если категория отсутствует —
 *    создаём новую через CategoryService.
 *
 * 5. После создания сохраняем
 *    GUID ↔ term_id.
 *
 * В результате CategoriesParser остаётся
 * максимально простым и отвечает только
 * за чтение CommerceML.
 */
final class CategoryImportService
{
	/**
	 * Выполняет поиск категории.
	 */
	private CategoryMatcher $matcher;

	/**
	 * Работает с категориями WooCommerce.
	 */
	private CategoryService $categories;

	/**
	 * Работает с картой GUID.
	 */
	private MappingStorage $storage;

	/**
	 * Конструктор.
	 */
	public function __construct()
	{
		$this->matcher = new CategoryMatcher();

		$this->categories = new CategoryService();

		$this->storage = new MappingStorage();
	}

	/**
	 * ======================================================
	 * Импортировать категорию.
	 * ======================================================
	 *
	 * Возвращает ID категории WooCommerce.
	 *
	 * Алгоритм:
	 *
	 * GUID
	 *   ↓
	 * CategoryMatcher
	 *   ↓
	 * найдена?
	 *   │
	 *   ├── Да → вернуть ID
	 *   │
	 *   └── Нет
	 *          ↓
	 *   создать категорию
	 *          ↓
	 *   сохранить GUID
	 *          ↓
	 *      вернуть ID
	 * 
	 * @throws \Throwable
	 */
	public function import(
		string $guid,
		string $name
	): int {

		/*
         * --------------------------------------------------
         * ШАГ 1.
         *
         * Пытаемся найти существующую категорию.
         * --------------------------------------------------
         */

		$categoryId = $this->matcher->match(
			$guid,
			$name
		);

		/*
         * --------------------------------------------------
         * Если категория уже существует,
         * ничего создавать не нужно.
         * --------------------------------------------------
         */

		if ($categoryId !== null) {

			return $categoryId;
		}

		/*
         * --------------------------------------------------
         * Категория отсутствует.
         *
         * Создаём новую.
         * --------------------------------------------------
         */

		$categoryId = $this->categories->create(
			$name,
			0
		);

		/*
         * --------------------------------------------------
         * После создания обязательно сохраняем
         * соответствие GUID ↔ term_id.
         *
         * Используем совместимый с EDI механизм.
         * --------------------------------------------------
         */

		try {

			$this->storage->remember(
				MappingKeys::CATEGORY,
				$guid,
				$categoryId
			);
		} catch (\Throwable $e) {

			imperia_log(
				sprintf(
					'ERROR: unable to save category mapping. GUID=%s, ID=%d. %s',
					$guid,
					$categoryId,
					$e->getMessage()
				)
			);

			throw $e;
		}

		/*
         * --------------------------------------------------
         * Пишем информацию в лог.
         * --------------------------------------------------
         */

		imperia_log(
			sprintf(
				'Created WooCommerce category "%s" (ID=%d).',
				$name,
				$categoryId
			)
		);

		/*
         * Возвращаем ID новой категории.
         */

		return $categoryId;
	}
}
