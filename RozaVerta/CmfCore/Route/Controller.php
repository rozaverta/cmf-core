<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 3:12
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Module\Exceptions\ExpectedModuleException;
use RozaVerta\CmfCore\Route\Interfaces\ControllerInterface;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Route\Interfaces\MountPointInterface;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\ApplicationTrait;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Log\Traits\LoggableTrait;
use RozaVerta\CmfCore\Traits\GetTrait;

abstract class Controller implements ControllerInterface
{
	use LoggableTrait;
	use GetTrait;
	use GetIdentifierTrait;
	use ModuleGetterTrait;
	use ApplicationTrait;

	/**
	 * @var MountPoint
	 */
	protected $mountPoint;

	/**
	 * @var array
	 */
	protected $items = [];

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var bool
	 */
	protected $cacheable = false;

	/**
	 * @var \RozaVerta\CmfCore\Support\Prop
	 */
	protected $properties;

	/**
	 * @var array
	 */
	protected $pageData = [];

	/**
	 * Controller constructor.
	 *
	 * @param MountPointInterface $mountPoint
	 * @param array $data
	 */
	public function __construct( MountPointInterface $mountPoint, array $data = [] )
	{
		$module = $mountPoint->getModule();
		$name = get_class($this);
		if( strpos($name, $module->getNamespaceName()) !== 0 )
		{
			throw new ExpectedModuleException("Invalid current module");
		}

		if( isset($data['id']) && is_int($data['id']) )
		{
			$this->setId($data['id']);
		}

		if( isset($data['cacheable']) )
		{
			$this->cacheable = (bool) $data['cacheable'];
		}

		unset($data['id'], $data['cacheable']);

		$this->appInit();
		$this->setModule($module);
		$this->mountPoint = $mountPoint;
		$this->items = $data;
		$this->properties = new Prop();
	}

	abstract public function ready(): bool;

	/**
	 * Get controller name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		if( empty($this->name) )
		{
			$name = $this->getModule()->getKey();
			if( preg_match('/Controllers\\\\(.*?)$/', static::class, $e ) )
			{
				$name .= '::' . preg_replace_callback( '/[A-Z]/', static function( $m ) { return '_' . lcfirst( $m[0] ); }, lcfirst( $e[1] ) );
				$name  = str_replace( '\\', ':', $name );
			}
			$this->name = strtolower( $name );
		}

		return $this->name;
	}

	public function isCacheable(): bool
	{
		return $this->cacheable;
	}

	/**
	 * @return MountPointInterface
	 */
	public function getMountPoint(): MountPointInterface
	{
		return $this->mountPoint;
	}

	/**
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function getProperty( string $name, $default = false )
	{
		return $this->properties->getOr($name, $default);
	}

	/**
	 * @return array
	 */
	public function getProperties(): array
	{
		return $this->properties->getAll();
	}

	/**
	 * @return array
	 */
	public function getPageData(): array
	{
		return $this->pageData;
	}

	/**
	 * Check support method for other module
	 *
	 * @param string | ModuleInterface $name
	 * @param string $method
	 * @return bool
	 */
	public function supportPortalMethod( $name, $method ): bool
	{
		if( $name instanceof ModuleInterface )
		{
			$name = $name->getKey();
		}

		return $this->module->support($name) && method_exists($this, $method);
	}

	public function change( ControllerInterface $controller ): bool
	{
		return true;
	}
}