<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 1:06
 */

namespace RozaVerta\CmfCore\Module\Interfaces;

interface ModuleManifestInterface extends ModularInterface
{
	public function getDescription(): string;

	public function getAuthors(): array;

	public function getLicenses(): array;

	/**
	 * Get manifest resource full data
	 *
	 * @return array
	 */
	public function getManifestData(): array;
}