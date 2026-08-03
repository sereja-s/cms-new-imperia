<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Hooks\Product;

/**
 * Подмена названия товара.
 *
 * EDI получает из XML обычное поле:
 *
 *     <Наименование>
 *
 * Однако в нашей интеграции принято использовать
 * штатное поле 1С
 *
 *     Полное наименование
 *
 * (его менеджеры обычно заполняют как
 * "Название для сайта" или "Название для печати").
 *
 * Мы НЕ изменяем код EDI.
 *
 * Мы просто подключаемся к фильтру,
 * который оставил разработчик плагина,
 * и подменяем значение name.
 *
 * После этого оригинальный ProductsParser
 * сохранит уже наше название.
 */
final class ProductPrintNameParser
{
	/**
	 * Регистрация обработчика.
	 */
	public function register(): void
	{
		add_filter(
			'edi_parse_product_xml_object',
			[$this, 'replaceProductName'],
			20,
			2
		);
	}

	/**
	 * Подмена названия товара.
	 *
	 * @param array $productData Данные товара, подготовленные EDI.
	 * @param array $xmlData      XML товара.
	 *
	 * @return array
	 */
	public function replaceProductName(
		array $productData,
		array $xmlData
	): array {

		$printName = $this->findPrintName($xmlData);

		/*
         * Если поле заполнено,
         * заменяем основное название товара.
         */
		if (!empty($printName)) {
			$productData['name'] = $printName;
		}

		return $productData;
	}

	/**
	 * Ищет значение реквизита
	 * "Полное наименование".
	 *
	 * В CommerceML он находится внутри
	 *
	 * <ЗначенияРеквизитов>
	 *
	 * Если реквизит отсутствует,
	 * возвращает null.
	 */
	private function findPrintName(array $xmlData): ?string
	{
		if (empty($xmlData['ЗначенияРеквизитов'])) {
			return null;
		}

		$properties = $xmlData['ЗначенияРеквизитов'][0]['#']['ЗначениеРеквизита'] ?? [];

		foreach ($properties as $property) {

			$propertyName = trim(
				(string) ($property['#']['Наименование'][0]['#'] ?? '')
			);

			/*
             * Нас интересует только
             * штатный реквизит 1С.
             */
			if ($propertyName !== 'Полное наименование') {
				continue;
			}

			$value = trim(
				(string) ($property['#']['Значение'][0]['#'] ?? '')
			);

			/*
             * Если поле заполнено,
             * возвращаем его.
             */
			if ($value !== '') {
				return $value;
			}

			break;
		}

		return null;
	}
}
