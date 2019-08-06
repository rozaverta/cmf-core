<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:10
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;
use RozaVerta\CmfCore\Route\MountLink;

/**
 * Interface RouterInterface
 *
 * @package RozaVerta\CmfCore\Route\Interfaces
 */
interface RouterInterface extends ModuleGetterInterface
{
	/**
	 * RouterInterface constructor.
	 *
	 * @param MountPointInterface $mountPoint
	 */
	public function __construct( MountPointInterface $mountPoint );

	/**
	 * Loads and prepares router settings.
	 *
	 * @return bool
	 */
	public function initialize(): bool;

	/**
	 * Get page mount point.
	 *
	 * @return MountPointInterface
	 */
	public function getMountPoint(): MountPointInterface;

	/**
	 * Get the page controller if the router has been initialized, or get null.
	 *
	 * @return ControllerInterface|null
	 */
	public function getController(): ? ControllerInterface;

	static public function exists( MountLink $mountLink ): bool;

	static public function makeUrl( MountLink $mountLink ): string;
}