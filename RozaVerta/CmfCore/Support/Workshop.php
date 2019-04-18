<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 22:25
 */

namespace RozaVerta\CmfCore\Support;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;
use RozaVerta\CmfCore\Log\Traits\LoggableTrait;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Traits\ApplicationProxyTrait;

abstract class Workshop implements WorkshopInterface
{
	use ApplicationProxyTrait;
	use LoggableTrait;
	use ModuleGetterTrait;

	public function __construct( WorkshopModuleProcessor $module )
	{
		$this->appInit();
		$this->setModule($module);
	}
}