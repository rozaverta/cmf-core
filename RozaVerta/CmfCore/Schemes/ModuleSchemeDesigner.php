<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 11:25
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;
use RozaVerta\CmfCore\Module\Module;
use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;

abstract class ModuleSchemeDesigner extends SchemeDesigner implements ModuleGetterInterface
{
	use ModuleGetterTrait {
		getModuleId as private getNativeModuleId;
	}

	protected function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format( $items, $platform );
		$items["module_id"] = (int) $items["module_id"];
		return $items;
	}

	public function getModuleId(): int
	{
		return $this->hasModule() ? $this->getNativeModuleId() : $this->items["module_id"];
	}

	/**
	 * @throws \ReflectionException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	protected function reloadModule()
	{
		$this->setModule( Module::module($this->items["module_id"]) );
	}
}