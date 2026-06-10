<?php

namespace Imperia\Modules\Catalog;

use Imperia\Core\ModuleInterface;

class Module implements ModuleInterface
{
	public function init(): void
	{
		imperia_log('Catalog module initialized');
	}
}
