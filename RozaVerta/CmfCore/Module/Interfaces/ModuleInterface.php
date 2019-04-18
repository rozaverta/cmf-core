<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 1:01
 */

namespace RozaVerta\CmfCore\Module\Interfaces;

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
	 * Get module instance
	 *
	 * @param int $id
	 * @return ModuleInterface
	 */
	static public function module( int $id ): ModuleInterface;
}