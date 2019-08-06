<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2018
 * Time: 0:22
 */

namespace RozaVerta\CmfCore\Module\Interfaces;

use RozaVerta\CmfCore\Module\Module;

interface ModuleGetterInterface
{
	/**
	 * Get module instance
	 *
	 * @return \RozaVerta\CmfCore\Module\Module
	 */
	public function getModule(): Module;

	/**
	 * Get module id
	 *
	 * @return int
	 */
	public function getModuleId(): int;

	/**
	 * Has module loaded
	 *
	 * @return bool
	 */
	public function hasModule(): bool;
}