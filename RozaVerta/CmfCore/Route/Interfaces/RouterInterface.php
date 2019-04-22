<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:10
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;
use RozaVerta\CmfCore\Route\MountLink;

interface RouterInterface extends ModuleGetterInterface
{
	public function __construct( MountPointInterface $mountPoint );

	public function ready(): bool;

	/**
	 * @return MountPointInterface
	 */
	public function getMountPoint(): MountPointInterface;

	public function getController(): ? ControllerInterface;

	static public function exists( MountLink $mountLink ): bool;

	static public function makeUrl( MountLink $mountLink ): string;
}