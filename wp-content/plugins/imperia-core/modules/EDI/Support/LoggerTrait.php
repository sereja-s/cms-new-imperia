<?php

declare(strict_types=1);

namespace Imperia\Modules\EDI\Support;

use BytePerfect\EDI\EDI;

/**
 * ==========================================================
 * LoggerTrait
 * ==========================================================
 *
 * Общий функционал всех парсеров Imperia.
 *
 * Trait используется вместо базового класса,
 * потому что наши парсеры наследуются
 * от классов EDI.
 *
 * Здесь находится только общий код,
 * который понадобится нескольким парсерам.
 *
 * Бизнес-логики здесь быть НЕ должно.
 *
 */
trait LoggerTrait
{
	/**
	 * Записать сообщение уровня Debug.
	 */
	protected function debug(string $message): void
	{
		EDI::log()->debug($message);
	}

	/**
	 * Записать предупреждение.
	 */
	protected function warning(string $message): void
	{
		EDI::log()->warning($message);
	}

	/**
	 * Записать ошибку.
	 */
	protected function error(string $message): void
	{
		EDI::log()->error($message);
	}

	/**
	 * Красивый лог начала обработки.
	 */
	protected function start(string $entity): void
	{
		$this->debug("Start processing {$entity}");
	}

	/**
	 * Красивый лог окончания обработки.
	 */
	protected function finish(string $entity): void
	{
		$this->debug("Finish processing {$entity}");
	}
}
