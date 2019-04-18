<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2019
 * Time: 17:22
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Route\MountPoint;

interface ControllerInterface extends ModuleGetterInterface
{
	public function __construct( MountPoint $point, array $data = [] );

	/**
	 * Ready (initial) page data
	 *
	 * @return bool
	 */
	public function ready(): bool;

	/**
	 * Get page identifier
	 *
	 * @return mixed
	 */
	public function getId(): int;

	/**
	 * Get controller name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Page is cacheable
	 *
	 * @return bool
	 */
	public function isCacheable(): bool;

	/**
	 * Complete. Load all data for page
	 *
	 * @return mixed
	 */
	public function complete();

	/**
	 * Get page property item
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getProperty( string $name, $default = false );

	/**
	 * Get all page properties
	 *
	 * @return array
	 */
	public function getProperties(): array;

	/**
	 * Get page data
	 *
	 * @return array
	 */
	public function getPageData(): array;

	/**
	 * Check support method for other module
	 *
	 * @param string | ModuleInterface $name
	 * @param string $method
	 * @return bool
	 */
	public function supportPortalMethod( $name, $method ): bool;

	/**
	 * Run this method before change page controller
	 *
	 * @param ControllerInterface $controller
	 * @return bool
	 */
	public function change( ControllerInterface $controller ): bool;
}