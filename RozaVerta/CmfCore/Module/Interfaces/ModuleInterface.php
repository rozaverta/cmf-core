<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 1:01
 */

namespace RozaVerta\CmfCore\Module\Interfaces;

use RozaVerta\CmfCore\Module\ResourceJson;

interface ModuleInterface extends ModularInterface
{
	/**
	 * Get module identifier
	 *
	 * @return int
	 */
	public function getId(): int;

	/**
	 * Has the module been installed?
	 *
	 * @return bool
	 */
	public function isInstall(): bool;

	/**
	 * Get module configuration
	 *
	 * @return ModuleManifestInterface
	 */
	public function getManifest(): ModuleManifestInterface;

	/**
	 * Create new ResourceJson module object from resources/{$name}.json file.
	 *
	 * @param string      $name
	 * @param null|string $cacheVersion
	 *
	 * @return ResourceJson
	 */
	public function getResourceJson( string $name, ?string $cacheVersion = null ): ResourceJson;

	/**
	 * Get module instance
	 *
	 * @param int $id
	 * @return ModuleInterface
	 */
	static public function module( int $id ): ModuleInterface;
}