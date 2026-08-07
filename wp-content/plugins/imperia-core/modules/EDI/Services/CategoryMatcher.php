<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Services;

use Imperia\Modules\EDI\Infrastructure\MappingKeys;
use Imperia\Modules\EDI\Infrastructure\MappingStorage;

/**
 * ==========================================================
 * CategoryMatcher
 * ==========================================================
 *
 * Выполняет сопоставление категорий CommerceML
 * с категориями WooCommerce.
 *
 * ----------------------------------------------------------
 * Назначение
 * ----------------------------------------------------------
 *
 * Данный класс отвечает исключительно
 * за поиск существующей категории.
 *
 * Он НЕ:
 *
 * • читает XML;
 * • создаёт категории;
 * • обновляет категории;
 * • работает с WooCommerce напрямую.
 *
 * Эти обязанности находятся
 * в специализированных сервисах.
 *
 * ----------------------------------------------------------
 * Алгоритм
 * ----------------------------------------------------------
 *
 * 1. Поиск категории по GUID.
 *
 * 2. Если GUID найден —
 *    проверяем существование категории.
 *
 * 3. Если категория удалена —
 *    удаляем устаревшую связь GUID.
 *
 * 4. Если GUID неизвестен —
 *    ищем категорию по названию.
 *
 * 5. Если нашли —
 *    сохраняем новую связь GUID ↔ term_id.
 *
 * 6. Если не нашли —
 *    возвращаем null.
 */
final class CategoryMatcher
{
	/**
	 * Сервис работы с категориями WooCommerce.
	 */
	private CategoryService $categories;

	/**
	 * Хранилище соответствий GUID ↔ term_id.
	 */
	private MappingStorage $storage;

	/**
	 * Конструктор.
	 */
	public function __construct()
	{
		$this->categories = new CategoryService();

		$this->storage = new MappingStorage();
	}

	/**
	 * ======================================================
	 * Найти категорию WooCommerce.
	 * ======================================================
	 *
	 * Возвращает:
	 *
	 * • ID найденной категории;
	 * • либо null.
	 *
	 * @throws \Throwable
	 */
	public function match(
		string $guid,
		string $name
	): ?int {

		/*
		 * --------------------------------------------------
		 * ШАГ 1.
		 *
		 * Пытаемся найти категорию
		 * по ранее сохранённому GUID.
		 * --------------------------------------------------
		 */

		$categoryId = $this->storage->find(
			MappingKeys::CATEGORY,
			$guid
		);

		/*
		 * --------------------------------------------------
		 * GUID найден.
		 *
		 * Проверяем,
		 * существует ли категория.
		 * --------------------------------------------------
		 */

		if ($categoryId !== null) {

			if ($this->categories->exists($categoryId)) {

				/*
				 * Временное диагностическое сообщение.
				 * После завершения тестирования
				 * будет удалено.
				 */

				/* imperia_log(
					sprintf(
						'CategoryMatcher: category found by GUID "%s" (ID=%d).',
						$guid,
						$categoryId
					)
				); */

				return $categoryId;
			}

			/*
			 * Категория была удалена вручную.
			 *
			 * Удаляем устаревшую связь GUID.
			 */

			$this->storage->forget(
				MappingKeys::CATEGORY,
				$guid
			);

			imperia_log(
				sprintf(
					'CategoryMatcher: removed obsolete mapping for GUID "%s".',
					$guid
				)
			);
		}

		/*
		 * --------------------------------------------------
		 * ШАГ 2.
		 *
		 * GUID неизвестен.
		 *
		 * Пытаемся найти категорию
		 * по названию.
		 * --------------------------------------------------
		 */

		$term = $this->categories->findByName(
			$name
		);

		/*
		 * Категория отсутствует.
		 *
		 * Сообщаем вызывающему коду,
		 * что необходимо создать новую.
		 */

		if (!$term) {

			/* imperia_log(
				sprintf(
					'CategoryMatcher: category "%s" not found. A new category will be created.',
					$name
				)
			); */

			return null;
		}

		/*
		 * --------------------------------------------------
		 * Категория найдена.
		 *
		 * Запоминаем новое соответствие
		 * GUID ↔ term_id.
		 * --------------------------------------------------
		 */

		$termId = (int) $term->term_id;

		try {

			$this->storage->remember(
				MappingKeys::CATEGORY,
				$guid,
				$termId
			);
		} catch (\Throwable $exception) {

			imperia_log(
				sprintf(
					'ERROR: unable to save category mapping for "%s". %s',
					$name,
					$exception->getMessage()
				)
			);

			throw $exception;
		}

		/*
		 * Временное диагностическое сообщение.
		 * После завершения тестирования
		 * будет удалено.
		 */

		/* imperia_log(
			sprintf(
				'CategoryMatcher: category "%s" matched by name (ID=%d).',
				$name,
				$termId
			)
		); */

		return $termId;
	}
}
