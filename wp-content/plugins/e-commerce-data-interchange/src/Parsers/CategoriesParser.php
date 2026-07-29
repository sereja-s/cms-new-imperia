<?php

declare(strict_types=1);


namespace BytePerfect\EDI\Parsers;


use BytePerfect\EDI\EDI;
use BytePerfect\EDI\Utils;
use Exception;


class CategoriesParser
{
	const CATEGORY_MAP_KEY = '_edi_1c_category_map';


	public function __construct()
	{
		add_action(
			'_/КоммерческаяИнформация/Классификатор/Категории/Категория',
			array($this, 'process')
		);
	}


	public function process(DataXML $xml_object): void
	{
		$category_data = $this->parse_xml_object($xml_object);
		try {
			$this->process_category($category_data, 0);
		} catch (Exception $e) {
			EDI::log()->error("Error processing category GUID {$category_data['Ид'][0]['#']}.");
			EDI::log()->error($e->getMessage());
		}
	}


	protected function parse_xml_object(DataXML $xml_object): array
	{
		$xml_data = $xml_object->GetArray();

		return $xml_data['Категория']['#'];
	}


	/* protected function process_category(array $category, int $parent_id): void
	{
		$guid = $category['Ид'][0]['#'];
		$name = $category['Наименование'][0]['#'];

		$category_id = Utils::get_guid_id_match(
			apply_filters('edi_category_map_key', self::CATEGORY_MAP_KEY),
			$guid
		);

		if (
			is_null($category_id)
			||
			! get_term_by('id', $category_id, 'product_cat')
		) {
			$category_id = $this->create_category($name, $parent_id);

			Utils::set_guid_id_match(
				apply_filters('edi_category_map_key', self::CATEGORY_MAP_KEY),
				$guid,
				$category_id
			);
		} else {
			$this->update_category($category_id, $name);
		}
	} */


	/**
	 * Обрабатывает одну категорию из CommerceML.
	 *
	 * Алгоритм работы:
	 *
	 * 1. Ищем категорию по UUID.
	 *    Если нашли — ничего не изменяем.
	 *
	 * 2. Если UUID неизвестен —
	 *    ищем категорию по названию.
	 *
	 * 3. Если нашли по названию —
	 *    сохраняем соответствие UUID ↔ ID.
	 *
	 * 4. Если не нашли —
	 *    создаём новую категорию в корне каталога
	 *    и также сохраняем UUID ↔ ID.
	 *
	 * Названия существующих категорий
	 * принципиально НЕ обновляются.
	 */
	protected function process_category(array $category, int $parent_id): void
	{

		// =====================================================
		// Получаем GUID и название категории из XML
		// =====================================================

		$guid = $category['Ид'][0]['#'];
		$name = $category['Наименование'][0]['#'];

		// =====================================================
		// 1. Сначала ищем категорию по GUID
		// =====================================================

		$category_id = Utils::get_guid_id_match(
			apply_filters(
				'edi_category_map_key',
				self::CATEGORY_MAP_KEY
			),
			$guid
		);

		// =====================================================
		// Если GUID найден, но самой категории уже нет,
		// считаем связь устаревшей.
		//
		// Пока просто забываем её.
		// На следующем шаге научимся удалять её из БД.
		// =====================================================

		if (
			$category_id &&
			! get_term_by('id', $category_id, 'product_cat')
		) {

			$category_id = null;

			EDI::log()->debug(
				sprintf(
					'Category with GUID "%s" was deleted. Searching again.',
					$guid
				)
			);
		}

		// =====================================================
		// 2. Если GUID не найден —
		// ищем категорию по названию.
		// =====================================================

		if (is_null($category_id)) {

			$term = get_term_by(
				'name',
				$name,
				'product_cat'
			);

			if ($term) {

				$category_id = (int) $term->term_id;

				// Запоминаем соответствие GUID -> term_id

				Utils::set_guid_id_match(
					apply_filters(
						'edi_category_map_key',
						self::CATEGORY_MAP_KEY
					),
					$guid,
					$category_id
				);

				EDI::log()->debug(
					sprintf(
						'Existing category "%s" linked with GUID.',
						$name
					)
				);
			}
		}

		// =====================================================
		// 3. Если не нашли даже по названию,
		// создаём новую категорию.
		//
		// Пока всегда создаём в корне каталога.
		// =====================================================

		if (is_null($category_id)) {

			$category_id = $this->create_category(
				$name,
				0
			);

			Utils::set_guid_id_match(
				apply_filters(
					'edi_category_map_key',
					self::CATEGORY_MAP_KEY
				),
				$guid,
				$category_id
			);

			EDI::log()->debug(
				sprintf(
					'Created new category "%s".',
					$name
				)
			);
		}

		// =====================================================
		// Всё.
		//
		// Если категория уже существовала —
		// ничего больше НЕ делаем.
		//
		// Название не обновляем.
		// Родителя не меняем.
		// Slug не меняем.
		// =====================================================
	}


	protected function sanitize_category_name(string $category_name): string
	{
		return wc_clean(wp_unslash($category_name));
	}


	protected function sanitize_category_slug(string $category_name): string
	{
		$category_slug = $this->sanitize_category_name($category_name);
		$category_slug = wc_sanitize_taxonomy_name($category_slug);
		$category_slug = Utils::transliterate($category_slug);

		return substr($category_slug, 0, 200);
	}


	/**
	 * Ищет категорию WooCommerce по названию.
	 *
	 * Используется в случае, если UUID категории из 1С ещё
	 * не сопоставлен ни с одной категорией сайта.
	 *
	 * Возвращает:
	 *  - ID категории, если она найдена;
	 *  - null, если категории нет.
	 */
	protected function find_category_by_name(string $name): ?int
	{

		$name = $this->sanitize_category_name($name);

		$term = get_term_by(
			'name',
			$name,
			'product_cat'
		);

		if ($term) {
			return (int) $term->term_id;
		}

		return null;
	}


	protected function create_category(string $name, int $parent_id): int
	{
		if ($parent_id) {
			$parent = get_term_by('id', $parent_id, 'product_cat');
			if (! $parent) {
				throw new Exception(

					sprintf(__('Product category parent is invalid: %d', 'edi'), $parent_id)
				);
			}
		}

		$name = $this->sanitize_category_name($name);
		$args = array(
			'parent' => $parent_id,
			'slug'   => $this->sanitize_category_slug($name),
		);

		$insert = wp_insert_term($name, 'product_cat', $args);
		if (is_wp_error($insert)) {
			throw new Exception(
				sprintf(

					__('Error create product category: %s', 'edi'),
					$insert->get_error_message()
				)
			);
		}

		EDI::log()->debug(
			sprintf(

				__('Product category was created: %s', 'edi'),
				$name
			)
		);

		return $insert['term_id'];
	}


	protected function update_category(int $term_id, string $name): void
	{
		global $wpdb;

		$name = $this->sanitize_category_name($name);

		$result = $wpdb->update($wpdb->terms, compact('name'), compact('term_id'));
		if (false === $result) {
			EDI::log()->error($wpdb->last_error);

			throw new Exception(
				sprintf(

					__('Cannot update product category: %s', 'edi'),
					wc_print_r(compact('name', 'term_id'), true)
				)
			);
		} elseif (0 === $result) {
			EDI::log()->warning(
				sprintf(

					__('Product category was not updated: %s', 'edi'),
					wc_print_r(compact('name', 'term_id'), true)
				)
			);
		} else {
			EDI::log()->debug(
				sprintf(

					__('Product category was updated: %s', 'edi'),
					$name
				)
			);
		}
	}
}
