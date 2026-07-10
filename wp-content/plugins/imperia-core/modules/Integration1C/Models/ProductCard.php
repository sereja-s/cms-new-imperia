<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\Models;

use Imperia\Modules\Integration1C\Collections\ImageCollection;
use Imperia\Modules\Integration1C\Contracts\ModelInterface;
use Imperia\Modules\Integration1C\ValueObjects\FullDescription;
use Imperia\Modules\Integration1C\ValueObjects\Price;
use Imperia\Modules\Integration1C\ValueObjects\ProductName;
use Imperia\Modules\Integration1C\ValueObjects\ProductUuid;
use Imperia\Modules\Integration1C\ValueObjects\ShortDescription;
use Imperia\Modules\Integration1C\ValueObjects\Sku;
use Imperia\Modules\Integration1C\ValueObjects\WebsiteProductName;

/**
 * ==========================================================================
 * PRODUCT CARD
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Представляет карточку товара,
 * полученную из информационной системы 1С.
 *
 * Является основной доменной моделью товара
 * в процессе подготовки обмена.
 *
 * --------------------------------------------------------------------------
 * Что хранит модель
 * --------------------------------------------------------------------------
 *
 * • идентификатор товара;
 * • основное название товара;
 * • название товара для сайта;
 * • артикул;
 * • цену;
 * • короткое описание;
 * • полное описание;
 * • группу товара 1С;
 * • изображения товара.
 *
 * --------------------------------------------------------------------------
 * Ответственность
 * --------------------------------------------------------------------------
 *
 * • объединение данных товара;
 * • предоставление доступа к данным;
 * • определение названия для использования на сайте;
 * • сравнение карточек товаров.
 *
 * --------------------------------------------------------------------------
 * Не отвечает за
 * --------------------------------------------------------------------------
 *
 * • создание товара WooCommerce;
 * • импорт товара;
 * • сохранение данных;
 * • загрузку изображений;
 * • генерацию SEO;
 * • проверку категорий;
 * • обмен с 1С.
 *
 * --------------------------------------------------------------------------
 * Immutable
 * --------------------------------------------------------------------------
 *
 * После создания объект не изменяется.
 */
final class ProductCard implements ModelInterface
{
	/**
	 * Уникальный идентификатор товара.
	 */
	private ProductUuid $productUuid;


	/**
	 * Основное название товара.
	 */
	private ProductName $productName;


	/**
	 * Название товара для сайта.
	 *
	 * Может отсутствовать.
	 */
	private ?WebsiteProductName $websiteProductName;


	/**
	 * Артикул товара.
	 */
	private Sku $sku;


	/**
	 * Цена товара.
	 */
	private Price $price;


	/**
	 * Короткое описание товара.
	 */
	private ShortDescription $shortDescription;


	/**
	 * Полное описание товара.
	 *
	 * Может отсутствовать.
	 */
	private ?FullDescription $fullDescription;


	/**
	 * Группа товара из 1С.
	 */
	private GroupReference $groupReference;


	/**
	 * Коллекция изображений товара.
	 */
	private ImageCollection $images;


	/**
	 * Создаёт карточку товара.
	 */
	public function __construct(
		ProductUuid $productUuid,
		ProductName $productName,
		?WebsiteProductName $websiteProductName,
		Sku $sku,
		Price $price,
		ShortDescription $shortDescription,
		?FullDescription $fullDescription,
		GroupReference $groupReference,
		ImageCollection $images
	) {
		$this->productUuid = $productUuid;
		$this->productName = $productName;
		$this->websiteProductName = $websiteProductName;
		$this->sku = $sku;
		$this->price = $price;
		$this->shortDescription = $shortDescription;
		$this->fullDescription = $fullDescription;
		$this->groupReference = $groupReference;
		$this->images = $images;
	}


	/**
	 * Возвращает уникальный идентификатор товара.
	 */
	public function productUuid(): ProductUuid
	{
		return $this->productUuid;
	}


	/**
	 * Возвращает основное название товара.
	 */
	public function productName(): ProductName
	{
		return $this->productName;
	}


	/**
	 * Возвращает название товара для сайта.
	 */
	public function websiteProductName(): ?WebsiteProductName
	{
		return $this->websiteProductName;
	}


	/**
	 * Возвращает название товара,
	 * которое должно использоваться на сайте.
	 *
	 * Если специальное название для сайта
	 * отсутствует, используется основное название.
	 */
	public function displayName(): ProductName|WebsiteProductName
	{
		return $this->websiteProductName
			?? $this->productName;
	}


	/**
	 * Возвращает артикул товара.
	 */
	public function sku(): Sku
	{
		return $this->sku;
	}


	/**
	 * Возвращает цену товара.
	 */
	public function price(): Price
	{
		return $this->price;
	}


	/**
	 * Возвращает короткое описание товара.
	 */
	public function shortDescription(): ShortDescription
	{
		return $this->shortDescription;
	}


	/**
	 * Возвращает полное описание товара.
	 */
	public function fullDescription(): ?FullDescription
	{
		return $this->fullDescription;
	}


	/**
	 * Возвращает группу товара.
	 */
	public function groupReference(): GroupReference
	{
		return $this->groupReference;
	}


	/**
	 * Возвращает коллекцию изображений.
	 */
	public function images(): ImageCollection
	{
		return $this->images;
	}


	/**
	 * Проверяет идентичность товара.
	 *
	 * Сравнение выполняется по UUID 1С.
	 */
	public function sameIdentity(ProductCard $other): bool
	{
		return $this->productUuid->equals(
			$other->productUuid()
		);
	}


	/**
	 * Сравнивает две карточки товара.
	 */
	public function equals(ProductCard $other): bool
	{
		return
			$this->sameIdentity($other)
			&&
			$this->productName->equals($other->productName())
			&&
			$this->websiteNameEquals($other)
			&&
			$this->sku->equals($other->sku())
			&&
			$this->price->equals($other->price())
			&&
			$this->shortDescription->equals($other->shortDescription())
			&&
			$this->fullDescriptionEquals($other)
			&&
			$this->groupReference->equals($other->groupReference())
			&&
			$this->images->equals($other->images());
	}


	/**
	 * Сравнивает названия товара для сайта.
	 */
	private function websiteNameEquals(ProductCard $other): bool
	{
		if (
			$this->websiteProductName === null
			&& $other->websiteProductName() === null
		) {
			return true;
		}

		if (
			$this->websiteProductName === null
			|| $other->websiteProductName() === null
		) {
			return false;
		}

		return $this->websiteProductName->equals(
			$other->websiteProductName()
		);
	}


	/**
	 * Сравнивает полные описания товара.
	 */
	private function fullDescriptionEquals(ProductCard $other): bool
	{
		if (
			$this->fullDescription === null
			&& $other->fullDescription() === null
		) {
			return true;
		}

		if (
			$this->fullDescription === null
			|| $other->fullDescription() === null
		) {
			return false;
		}

		return $this->fullDescription->equals(
			$other->fullDescription()
		);
	}
}
