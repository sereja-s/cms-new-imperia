<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\Collections;

use Imperia\Modules\Integration1C\Contracts\CollectionInterface;
use Imperia\Modules\Integration1C\Models\ProductImage;
use InvalidArgumentException;

/**
 * ==========================================================================
 * IMAGE COLLECTION
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Представляет коллекцию изображений товара
 * в доменной модели Integration1C.
 *
 * Используется внутри ProductCard
 * для хранения набора изображений,
 * полученных из информационной системы 1С.
 *
 * --------------------------------------------------------------------------
 * Ответственность
 * --------------------------------------------------------------------------
 *
 * • хранение объектов ProductImage;
 * • обеспечение корректности набора изображений;
 * • сортировка изображений по порядковому номеру;
 * • предоставление единого API работы с изображениями.
 *
 * --------------------------------------------------------------------------
 * Не отвечает за
 * --------------------------------------------------------------------------
 *
 * • загрузку изображений;
 * • получение файлов;
 * • генерацию ALT;
 * • создание WordPress attachment;
 * • обмен с 1С или WooCommerce.
 *
 * --------------------------------------------------------------------------
 * Инварианты
 * --------------------------------------------------------------------------
 *
 * • коллекция содержит только ProductImage;
 * • порядок изображений уникален;
 * • изображения отсортированы по ImageOrder;
 * • после создания коллекция не изменяется;
 * • объект является immutable.
 *
 * --------------------------------------------------------------------------
 * Особенности
 * --------------------------------------------------------------------------
 *
 * Изображение с ImageOrder = 1
 * считается главным изображением.
 */
final class ImageCollection implements CollectionInterface
{
	/**
	 * Изображения товара.
	 *
	 * @var ProductImage[]
	 */
	private array $images;


	/**
	 * Создаёт коллекцию изображений.
	 *
	 * @param ProductImage ...$images Изображения товара.
	 *
	 * @throws InvalidArgumentException
	 * Если обнаружены повторяющиеся порядковые номера.
	 */
	public function __construct(ProductImage ...$images)
	{
		$this->validateUniqueOrder($images);

		$this->images = $images;

		$this->sortImages();
	}


	/**
	 * Возвращает все изображения коллекции.
	 *
	 * @return ProductImage[]
	 */
	public function all(): array
	{
		return $this->images;
	}


	/**
	 * Возвращает главное изображение товара.
	 *
	 * Главное изображение имеет ImageOrder = 1.
	 *
	 * @return ProductImage|null
	 */
	public function primary(): ?ProductImage
	{
		foreach ($this->images as $image) {

			if ($image->isPrimary()) {
				return $image;
			}
		}

		return null;
	}


	/**
	 * Возвращает количество изображений.
	 */
	public function count(): int
	{
		return count($this->images);
	}


	/**
	 * Проверяет,
	 * является ли коллекция пустой.
	 */
	public function isEmpty(): bool
	{
		return $this->images === [];
	}


	/**
	 * Сравнивает две коллекции изображений.
	 *
	 * @param ImageCollection $other
	 *
	 * @return bool
	 */
	public function equals(ImageCollection $other): bool
	{
		if ($this->count() !== $other->count()) {
			return false;
		}


		foreach ($this->images as $index => $image) {

			if (!$image->equals($other->images[$index])) {
				return false;
			}
		}


		return true;
	}


	/**
	 * Проверяет уникальность порядковых номеров.
	 *
	 * Два изображения не могут иметь
	 * одинаковый ImageOrder.
	 *
	 * @param ProductImage[] $images
	 *
	 * @throws InvalidArgumentException
	 */
	private function validateUniqueOrder(array $images): void
	{
		$orders = [];


		foreach ($images as $image) {

			$order = $image->imageOrder()->value();


			if (in_array($order, $orders, true)) {

				throw new InvalidArgumentException(
					'Коллекция изображений не может содержать '
						. 'одинаковые порядковые номера.'
				);
			}


			$orders[] = $order;
		}
	}


	/**
	 * Сортирует изображения
	 * по порядковому номеру.
	 */
	private function sortImages(): void
	{
		usort(
			$this->images,
			static function (
				ProductImage $first,
				ProductImage $second
			): int {

				return
					$first->imageOrder()->value()
					<=>
					$second->imageOrder()->value();
			}
		);
	}
}
