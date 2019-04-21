<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
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
			throw new ResourceReadException("The resource must be a json data file, the selected type is '{$ext}'");
		}

		$this->setModule($module);

		if($cacheVersion)
		{
			$file = Path::resources( $module->getId() . "/" . $cacheVersion . "/" . $file );
		}
		else
		{
			$file = Path::module( $module, $file, "resources" );
			if( !file_exists($file))
			{
				$file = Path::addons($module->getKey() . "/resources/" . $file );
			}
		}

		$filePath = $file;
		$file = new \SplFileInfo(realpath($file));
		$this->pathname = $file->getPathname();

		if( ! $file->isFile() )
		{
			throw new ResourceNotFoundException("The resource file '{$filePath}' not found");
		}

		$this->path = $file->getPath();
		$this->name = $file->getBasename(".json");
		$this->raw = @ file_get_contents($this->pathname);
		if( ! $this->raw )
		{
			throw new ResourceReadException("Cannot ready resource file '{$this->name}'");
		}

		try {
			$data = Json::parse($this->raw, true);
			if( ! is_array($data) )
			{
				throw new JsonParseException("Resource data is not array");
			}
		}
		catch( JsonParseException $e ) {
			throw new ResourceReadException("Cannot read resource file '{$this->name}', json parser error: " . $e->getCode());
		}

		if( isset($data['type']) && is_string($data['type']) )
		{
			$this->type = $data['type'];
		}

		$this->items = $data;
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
}