<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 3:12
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Controllers\Redirect;
use RozaVerta\CmfCore\Module\Exceptions\ExpectedModuleException;
use RozaVerta\CmfCore\Route\Exceptions\PageNotFoundException;
use RozaVerta\CmfCore\Route\Interfaces\ControllerInterface;
use RozaVerta\CmfCore\Route\Interfaces\MountPointInterface;
use RozaVerta\CmfCore\Route\Interfaces\RouterInterface;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Traits\ServiceTrait;

/**
 * Class Router
 *
 * @package RozaVerta\CmfCore\Route
 */
abstract class Router implements RouterInterface
{
	use ModuleGetterTrait;
	use ServiceTrait;

	protected $controller = null;

	/**
	 * @var MountPointInterface
	 */
	protected $mountPoint;

	public function __construct( MountPointInterface $mountPoint )
	{
		$module = $mountPoint->getModule();
		$name = get_class($this);
		if( strpos($name, $module->getNamespaceName()) !== 0 )
		{
			throw new ExpectedModuleException("Invalid current module");
		}

		$this->mountPoint = $mountPoint;
		$this->setModule($module);
	}

	/**
	 * @return MountPointInterface
	 */
	public function getMountPoint(): MountPointInterface
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
	 * @param string     $controller
	 * @param array|null $properties
	 * @return bool
	 */
	protected function createController( string $controller, ? array $properties = null ): bool
	{
		return $this->setController( new $controller($this->getModule(), $this->mountPoint, is_array($properties) ? $properties : $this->mountPoint->toArray()) );
	}

	/**
	 * Create and set new Redirect controller
	 *
	 * @param string $location
	 * @param bool $permanent
	 * @param bool $refresh
	 *
	 * @return bool
	 */
	protected function createRedirectController( string $location, bool $permanent = false, bool $refresh = false ): bool
	{
		return $this->setController(
			new Redirect( $this->mountPoint, compact( 'location', 'permanent', 'refresh' ) )
		);
	}

	/**
	 * Throw new PageNotFoundException
	 *
	 * @param string $text Not found message
	 *
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

	protected function checkController( string $name, & $className = null ): bool
	{
		$testClassName = $this->getControllerClassName( $name );
		if( class_exists( $testClassName, true ) )
		{
			$className = $testClassName;
			return false;
		}
		else
		{
			return false;
		}
	}
}