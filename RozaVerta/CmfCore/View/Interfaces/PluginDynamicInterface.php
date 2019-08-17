<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 22:36
 */

namespace RozaVerta\CmfCore\View\Interfaces;

use RozaVerta\CmfCore\Interfaces\Getter;

interface PluginDynamicInterface extends PluginInterface, Getter
{
	/**
	 * Load plugin data
	 *
	 * @param array $data
	 *
	 * @return $this
	 */
	public function ready( array $data = [] );

	/**
	 * Get plugin data
	 *
	 * @return array
	 */
	public function getPluginData(): array;

	/**
	 * Completion of loading and processing component data
	 */
	public function complete(): void;

	/**
	 * Get cache type.
	 * Valid values: nocache, data, view, plugin
	 *
	 * @return string
	 */
	public function getCacheType(): string;

	/**
	 * Get full cache data
	 *
	 * @return array
	 */
	public function getCacheData(): array;
}