<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\Collections;

use Imperia\Modules\Integration1C\Contracts\CollectionInterface;
use Imperia\Modules\Integration1C\Models\ProductCard;
use Imperia\Modules\Integration1C\ValueObjects\ProductUuid;
use InvalidArgumentException;

/**
 * ==========================================================================
 * PRODUCT COLLECTION
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Представляет коллекцию карточек товаров,
 * полученных из информационной системы 1С.
 *
 * Используется как единый объект
 * для хранения товаров
 * внутри процесса обмена.
 *
 * --------------------------------------------------------------------------
 * Ответственность
 * --------------------------------------------------------------------------
 *
 * • хранение объектов ProductCard;
 * • обеспечение уникальности товаров;
 * • поиск товара по UUID;
 * • проверка существования товара;
 * • сравнение коллекций.
 *
 * --------------------------------------------------------------------------
 * Не отвечает за
 * --------------------------------------------------------------------------
 *
 * • импорт товаров;
 * • создание товаров WooCommerce;
 * • чтение XML;
 * • сохранение данных;
 * • обмен с 1С.
 *
 * --------------------------------------------------------------------------
 * Инварианты
 * --------------------------------------------------------------------------
 *
 * • содержит только ProductCard;
 * • UUID товаров уникальны;
 * • после создания объект не изменяется;
 * • объект является immutable.
 */
final class ProductCollection implements CollectionInterface
{
	/**
	 * Карточки товаров.
	 *
	 * @var ProductCard[]
	 */
	private array $products;


	/**
	 * Создаёт коллекцию товаров.
	 *
	 * @param ProductCard ...$products
	 *
	 * @throws InvalidArgumentException
	 */
	public function __construct(ProductCard ...$products)
	{
		$this->validateUniqueProducts($products);

		$this->products = $products;
	}


	/**
	 * Возвращает все товары.
	 *
	 * @return ProductCard[]
	 */
	public function all(): array
	{
		return $this->products;
	}


	/**
	 * Возвращает количество товаров.
	 */
	public function count(): int
	{
		return count($this->products);
	}


	/**
	 * Проверяет,
	 * пуста ли коллекция.
	 */
	public function isEmpty(): bool
	{
		return $this->products === [];
	}


	/**
	 * Проверяет,
	 * существует ли товар.
	 */
	public function contains(ProductUuid $productUuid): bool
	{
		return $this->find($productUuid) !== null;
	}


	/**
	 * Возвращает товар по UUID.
	 */
	public function find(
		ProductUuid $productUuid
	): ?ProductCard {

		foreach ($this->products as $product) {

			if (
				$product
				->productUuid()
				->equals($productUuid)
			) {
				return $product;
			}
		}

		return null;
	}


	/**
	 * Сравнивает две коллекции.
	 */
	public function equals(
		ProductCollection $other
	): bool {

		if ($this->count() !== $other->count()) {
			return false;
		}

		foreach ($this->products as $index => $product) {

			if (
				!$product->equals(
					$other->all()[$index]
				)
			) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Проверяет уникальность UUID товаров.
	 *
	 * @param ProductCard[] $products
	 *
	 * @throws InvalidArgumentException
	 */
	private function validateUniqueProducts(
		array $products
	): void {

		$uuids = [];


		foreach ($products as $product) {

			$uuid = (string)$product
				->productUuid();


			if (
				in_array(
					$uuid,
					$uuids,
					true
				)
			) {
				throw new InvalidArgumentException(
					'Коллекция содержит товары '
						. 'с одинаковым UUID.'
				);
			}


			$uuids[] = $uuid;
		}
	}
}
