<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.07.2019
 * Time: 4:13
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

/**
 * Interface ControllerPackageInterface
 *
 * @package RozaVerta\CmfCore\Route\Interfaces
 */
interface ControllerPackageInterface
{
	/**
	 * Get view package name
	 *
	 * @return string|null
	 */
	public function getPackageName(): ?string;
}