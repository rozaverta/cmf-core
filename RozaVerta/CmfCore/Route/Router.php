<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 3:12
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Controllers\Redirect;
use RozaVerta\CmfCore\Module\Exceptions\ExpectedModuleException;
use RozaVerta\CmfCore\Route\Exceptions\PageNotFoundException;
use RozaVerta\CmfCore\Route\Interfaces\ControllerInterface;
use RozaVerta\CmfCore\Route\Interfaces\RouterInterface;
use RozaVerta\CmfCore\Traits\ApplicationTrait;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;

abstract class Router implements RouterInterface
{
	use ModuleGetterTrait;
	use ApplicationTrait;

	protected $controller = null;

	protected $mountPoint;

	public function __construct( MountPoint $mountPoint )
	{
		$module = $mountPoint->getModule();
		$name = get_class($this);
		if( strpos($name, $module->getNamespaceName()) !== 0 )
		{
			throw new ExpectedModuleException("Invalid current module");
		}

		$this->mountPoint = $mountPoint;
		$this->appInit();
		$this->setModule($module);
	}

	/**
	 * @return MountPoint
	 */
	public function getMountPoint(): MountPoint
	{
		return $this->mountPoint;
	}

	/**
	 * @return ControllerInterface | null
	 */
	public function getController(): ? ControllerInterface
	{
		return $this->controller;
	}

	// protected

	/**
	 * @param mixed $controller
	 * @return bool
	 */
	protected function setController( ControllerInterface $controller ): bool
	{
		$this->controller = $controller;
		return true;
	}

	/**
	 * @param mixed $controller
	 * @param array|null $properties
	 * @return bool
	 */
	protected function createController( string $controller, ? array $properties = null ): bool
	{
		return $this->setController( new $controller($this->getModule(), $this->mountPoint, is_array($properties) ? $properties : $this->mountPoint->toArray()) );
	}

	/**
	 * @param string $location
	 * @param bool $permanent
	 * @param bool $refresh
	 * @return bool
	 */
	protected function createRedirectController( string $location, bool $permanent = false, bool $refresh = false)
	{
		return $this->setController(
			new Redirect($this->getModule(), $this->mountPoint, compact('location', 'permanent', 'refresh'))
		);
	}

	/**
	 * @param string $text
	 * @throws PageNotFoundException
	 */
	protected function throw404( string $text = "" )
	{
		throw new PageNotFoundException($text);
	}

	protected function getControllerClassName( string $name ): string
	{
		return $this->getModule()->getNamespaceName() . "Controllers\\" . $name;
	}

	protected function checkController( string $name ): bool
	{
		return class_exists( $this->getControllerClassName($name), true );
	}
}