<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 3:12
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Events\ControllerCompleteEvent;
use RozaVerta\CmfCore\Module\Exceptions\ExpectedModuleException;
use RozaVerta\CmfCore\Route\Interfaces\ControllerInterface;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Route\Interfaces\MountPointInterface;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Log\Traits\LoggableTrait;
use RozaVerta\CmfCore\Traits\GetTrait;
use RozaVerta\CmfCore\Traits\ServiceTrait;

/**
 * Class Controller
 *
 * @package RozaVerta\CmfCore\Route
 */
abstract class Controller implements ControllerInterface
{
	use LoggableTrait;
	use GetTrait;
	use GetIdentifierTrait;
	use ModuleGetterTrait;
	use ServiceTrait;

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
	 * @var object|\RozaVerta\CmfCore\Event\EventManager
	 */
	protected $event;

	/**
	 * @var \RozaVerta\CmfCore\Http\Response
	 */
	protected $response;

	/**
	 * @var \RozaVerta\CmfCore\Http\Request
	 */
	protected $request;

	/**
	 * Controller constructor.
	 *
	 * @param MountPointInterface $mountPoint
	 * @param array               $data
	 *
	 * @throws \RozaVerta\CmfCore\Exceptions\ClassNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function __construct( MountPointInterface $mountPoint, array $data = [] )
	{
		$this->thisServices( "event", "response", "request" );

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

		$this->setModule($module);
		$this->mountPoint = $mountPoint;
		$this->items = $data;
		$this->properties = new Prop();
	}

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

	/**
	 * Page is cacheable
	 *
	 * @return bool
	 */
	public function isCacheable(): bool
	{
		return $this->cacheable;
	}

	/**
	 * Get page mount point
	 *
	 * @return MountPointInterface
	 */
	public function getMountPoint(): MountPointInterface
	{
		return $this->mountPoint;
	}

	/**
	 * Get page property item
	 *
	 * @param string $name
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getProperty( string $name, $default = false )
	{
		return $this->properties->getOr($name, $default);
	}

	/**
	 * Get all page properties
	 *
	 * @return array
	 */
	public function getProperties(): array
	{
		return $this->properties->getAll();
	}

	/**
	 * Get page data
	 *
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
	public function supportPortalMethod( $name, string $method ): bool
	{
		if( $name instanceof ModuleInterface )
		{
			$name = $name->getKey();
		}

		return $this->getModule()->support($name) && method_exists($this, $method);
	}

	/**
	 * Run this method before change page controller
	 *
	 * @param ControllerInterface $controller
	 * @return bool
	 */
	public function changeable( ControllerInterface $controller ): bool
	{
		return true;
	}

	/**
	 * Complete. Load all data for page
	 *
	 * @return void
	 *
	 * @throws \Throwable
	 */
	public function complete(): void
	{
		$event = new ControllerCompleteEvent($this, $this->pageData);
		self::service( "event" )->dispatch( $event );
		$this->pageData = $event->pageData;
	}
}