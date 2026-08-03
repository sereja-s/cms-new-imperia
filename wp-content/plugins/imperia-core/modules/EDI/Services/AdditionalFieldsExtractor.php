<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Services;

use Imperia\Modules\EDI\Configuration\AdditionalFieldNames;
use Imperia\Modules\EDI\DTO\AdditionalFields;
use Imperia\Modules\EDI\Infrastructure\StringNormalizer;

/**
 * ==========================================================
 * AdditionalFieldsExtractor
 * ==========================================================
 *
 * Извлекает дополнительные реквизиты товара
 * из CommerceML.
 *
 * ----------------------------------------------------------
 * Назначение
 * ----------------------------------------------------------
 *
 * Данный сервис знает:
 *
 * • где находятся дополнительные реквизиты;
 * • как они называются;
 * • как корректно сравнивать названия.
 *
 * Он НЕ знает:
 *
 * • как создавать товар;
 * • как обновлять товар;
 * • что такое WooCommerce;
 * • что такое ProductParser.
 *
 * Благодаря этому сервис можно использовать
 * повторно в любом месте проекта.
 */
final class AdditionalFieldsExtractor
{
	/**
	 * Извлечь дополнительные реквизиты.
	 *
	 * @param array $xmlData
	 *      Массив раздела "Товар".
	 */
	public function extract(array $xmlData): AdditionalFields
	{
		$fields = new AdditionalFields();

		if (!isset($xmlData['ЗначенияРеквизитов'])) {
			return $fields;
		}

		foreach (
			$xmlData['ЗначенияРеквизитов'][0]['#']['ЗначениеРеквизита']
			as $property
		) {

			$name = $property['#']['Наименование'][0]['#'] ?? '';
			$value = $property['#']['Значение'][0]['#'] ?? '';

			$normalized = StringNormalizer::normalize($name);

			if (
				$normalized ===
				StringNormalizer::normalize(
					AdditionalFieldNames::SITE_NAME
				)
			) {
				$fields->setSiteName($value);
				continue;
			}

			if (
				$normalized ===
				StringNormalizer::normalize(
					AdditionalFieldNames::SITE_SKU
				)
			) {
				$fields->setSiteSku($value);
				continue;
			}

			if (
				$normalized ===
				StringNormalizer::normalize(
					AdditionalFieldNames::SHORT_DESCRIPTION
				)
			) {
				$fields->setShortDescription($value);
			}
		}

		return $fields;
	}
}
