<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:10
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;
use RozaVerta\CmfCore\Route\MountPoint;

interface RouterInterface extends ModuleGetterInterface
{
	public function __construct( MountPoint $mountPoint );

	public function ready(): bool;

	/**
	 * @return MountPoint
	 */
	public function getMountPoint(): MountPoint;

	public function getController(): ? ControllerInterface;

	static public function exists( string $controller, int $id ): bool;

	static public function makeUrl( string $controller, int $id, string $context = null ): string;
}