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


	protected function process_category(array $category, int $parent_id): void
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
