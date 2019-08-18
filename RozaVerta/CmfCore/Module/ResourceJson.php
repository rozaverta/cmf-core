<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.03.2019
 * Time: 13:20
 */

namespace RozaVerta\CmfCore\Module;

use RozaVerta\CmfCore\Exceptions\JsonParseException;
use RozaVerta\CmfCore\Helper\Json;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException;
use RozaVerta\CmfCore\Module\Exceptions\ResourceReadException;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Traits\GetTrait;

/**
 * Class ResourceJson
 *
 * @package RozaVerta\CmfCore\Module
 */
class ResourceJson implements Interfaces\ResourceInterface
{
	use GetTrait;
	use Traits\ModuleGetterTrait;

	protected $pathname;
	protected $items = [];
	protected $type  = 'unknown';
	protected $name  = '';
	protected $path  = '';
	protected $raw   = '{}';

	private $addon = false;
	private $cacheVersion = null;

	/**
	 * Resource constructor.
	 *
	 * @param string $file
	 * @param ModuleInterface|null $module
	 * @param string|null $cacheVersion
	 *
	 * @throws ResourceNotFoundException
	 * @throws ResourceReadException
	 */
	public function __construct( $file, ModuleInterface $module, ?string $cacheVersion = null )
	{
		$ext = pathinfo($file, PATHINFO_EXTENSION);
		if( ! $ext )
		{
			$file .= ".json";
		}
		else if( $ext !== 'json' )
		{
			throw new ResourceReadException( "The resource must be a json data file, the selected type is \"{$ext}\"." );
		}

		$this->setModule($module);

		if($cacheVersion)
		{
			$file = Path::resources( $module->getId() . "/" . $cacheVersion . "/" . $file );
			$this->cacheVersion = $cacheVersion;
		}
		else
		{
			$file = Path::module( $module, $file, "resources" );
			if( !file_exists($file))
			{
				$file = Path::addons($module->getKey() . "/resources/" . $file );
				$this->addon = true;
			}
		}

		/** @var \SplFileInfo $file */

		$data = self::pathToJson( $file, $file, $this->raw );
		$this->pathname = $file->getPathname();
		$this->path = $file->getPath();
		$this->name = $file->getBasename(".json");

		if( isset($data['type']) && is_string($data['type']) )
		{
			$this->type = $data['type'];
		}

		$this->items = $data;
		if( $this->cacheVersion )
		{
			$this->addon = isset( $data["#/addon"] ) && $data["#/addon"] === true;
			unset( $this->items["#/addon"] );
		}
	}

	/**
	 * Get resource type
	 *
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * Resource location is addon
	 *
	 * @return bool
	 */
	public function isAddon(): bool
	{
		return $this->addon;
	}

	/**
	 * Resource loaded from cache version
	 *
	 * @param null $cacheVersion
	 * @return bool
	 */
	public function isCacheVersion( & $cacheVersion = null ): bool
	{
		if( is_null( $this->cacheVersion ) )
		{
			return false;
		}
		else
		{
			$cacheVersion = $this->cacheVersion;
			return true;
		}
	}

	/**
	 * Compare resource type
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function hasType( string $type ): bool
	{
		if( substr($type, 0, 2) !== "#/" )
		{
			$type = "#/{$type}";
		}
		return $this->getType() === $type;
	}

	/**
	 * Get the resource file path without filename
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Get the resource name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get the path to the resource file
	 *
	 * @return string
	 */
	public function getPathname(): string
	{
		return $this->pathname;
	}

	/**
	 * Get raw content
	 *
	 * @return string
	 */
	public function getRawData(): string
	{
		return $this->raw;
	}

	/**
	 * Reading JSON data from the path.
	 *
	 * @param string $path
	 * @param null   $file
	 * @param null   $raw
	 *
	 * @return array
	 *
	 * @throws ResourceNotFoundException
	 * @throws ResourceReadException
	 */
	public static function pathToJson( string $path, & $file = null, & $raw = null ): array
	{
		$path = realpath( $path );
		$file = new \SplFileInfo( $path );
		$name = $file->getBasename( ".json" );

		if( !$file->isFile() )
		{
			throw new ResourceNotFoundException( "The resource file \"{$path}\" not found." );
		}

		$raw = @ file_get_contents( $path );
		if( !$raw )
		{
			throw new ResourceReadException( "Cannot ready resource file \"{$name}\"." );
		}

		try
		{
			$data = Json::parse( $raw, true );
			if( !is_array( $data ) )
			{
				throw new JsonParseException( "The \"{$name}\" resource json data is not array." );
			}
		} catch( JsonParseException $e )
		{
			throw new ResourceReadException( "Cannot read resource file \"{$name}\", json parser error: \"" . $e->getCode() . '".' );
		}

		return $data;
	}
}