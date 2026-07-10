<?php

declare(strict_types=1);

namespace Imperia\Modules\Integration1C\Services;

use Imperia\Modules\Integration1C\Collections\ValidationMessageCollection;
use Imperia\Modules\Integration1C\Contracts\PackageValidatorInterface;
use Imperia\Modules\Integration1C\Models\ExchangePackage;
use Imperia\Modules\Integration1C\Models\PackageValidationResult;

/**
 * ==========================================================================
 * PACKAGE VALIDATOR
 * ==========================================================================
 *
 * Назначение
 * --------------------------------------------------------------------------
 * Выполняет общую проверку пакета обмена.
 *
 * Является координатором процесса проверки
 * и делегирует выполнение специализированным
 * валидаторам.
 *
 * Сам класс не содержит бизнес-правил проверки.
 *
 * --------------------------------------------------------------------------
 * Ответственность
 * --------------------------------------------------------------------------
 *
 * • организация процесса проверки;
 * • запуск специализированных валидаторов;
 * • объединение результатов проверки;
 * • возврат PackageValidationResult.
 *
 * --------------------------------------------------------------------------
 * Не отвечает за
 * --------------------------------------------------------------------------
 *
 * • проверку товаров;
 * • проверку категорий;
 * • проверку изображений;
 * • проверку SEO;
 * • импорт данных.
 *
 * --------------------------------------------------------------------------
 * Архитектура
 * --------------------------------------------------------------------------
 *
 * PackageValidator
 *
 *      │
 *      ├── ProductValidator
 *      ├── CategoryValidator
 *      ├── ImageValidator
 *      ├── SeoValidator
 *      └── ...
 *
 * На текущем этапе специализированные
 * валидаторы ещё не реализованы.
 */
final class PackageValidator implements PackageValidatorInterface
{
	/**
	 * Выполняет проверку пакета обмена.
	 */
	public function validate(
		ExchangePackage $package
	): PackageValidationResult {

		/*
		|--------------------------------------------------------------------------
		| Здесь постепенно будут вызываться специализированные валидаторы.
		|--------------------------------------------------------------------------
		|
		| ProductValidator
		| CategoryValidator
		| ImageValidator
		| SeoValidator
		|
		*/

		/*
		 * Коллекция сообщений проверки.
		 */
		$messages = new ValidationMessageCollection();

		/*
			$messages = $this->productValidator
			    ->validate($package, $messages);
			
			$messages = $this->categoryValidator
			    ->validate($package, $messages);
			
			$messages = $this->imageValidator
			    ->validate($package, $messages);
			
			$messages = $this->seoValidator
			    ->validate($package, $messages);
			*/

		return new PackageValidationResult(
			$messages
		);
	}
}
