<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 22:36
 */

namespace RozaVerta\CmfCore\View\Interfaces;

interface PluginDynamicInterface extends PluginInterface
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
	 *
	 * @return $this
	 */
	public function complete();

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