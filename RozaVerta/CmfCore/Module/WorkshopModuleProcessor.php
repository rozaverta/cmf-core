<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace RozaVerta\CmfCore\Module;

use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Module\Interfaces\ModuleManifestInterface;

/**
 * Class Module
 *
 * @package RozaVerta\CmfCore\Module
 */
final class WorkshopModuleProcessor extends Module
{
	/**
	 * Get new Manifest module object
	 *
	 * @return ModuleManifest
	 */
	public function getManifest(): ModuleManifestInterface
	{
		return $this->createManifest();
	}

	/**
	 * Get new WorkshopModuleProcessor object
	 *
	 * @param int $id
	 *
	 * @return WorkshopModuleProcessor
	 *
	 * @throws Exceptions\ModuleNotFoundException
	 * @throws Exceptions\ResourceNotFoundException
	 * @throws Exceptions\ResourceReadException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function module( int $id ): ModuleInterface
	{
		return self::create($id, false);
	}
}