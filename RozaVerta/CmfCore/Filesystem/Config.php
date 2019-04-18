<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 17:53
 */

namespace RozaVerta\CmfCore\Filesystem;

use RozaVerta\CmfCore\Filesystem\Exceptions\FileWriteException;
use RozaVerta\CmfCore\Filesystem\Exceptions\PathInvalidArgumentException;
use RozaVerta\CmfCore\Filesystem\Traits\WriteFileTrait;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Traits\GetTrait;
use RozaVerta\CmfCore\Traits\SetTrait;

class Config
{
	use WriteFileTrait;
	use SetTrait;
	use GetTrait;

	protected $items = [];

	protected $filename;

	protected $pathname;

	protected $path;

	protected $ready = null;

	/**
	 * Config constructor.
	 * @param string $name
	 */
	public function __construct( string $name )
	{
		if( preg_match('/\.php$/i', $name) )
		{
			$name = substr($name, 0, strlen($name) - 4);
		}

		$len = strlen($name);
		if( $len < 1 || $len > 32 || preg_match('/[^a-z0-9_]/', $name) || ! ctype_alpha($name[0]) )
		{
			throw new PathInvalidArgumentException("Invalid config name '{$name}'");
		}

		$this->filename = $name;
		$this->path = Path::config();
		$this->pathname = $this->path . $this->filename . ".php";
	}

	/**
	 * @return $this
	 */
	public function reload()
	{
		if( $this->fileExists() )
		{
			$this->items = Path::getIncludeData($this->pathname);
		}
		else
		{
			$this->items = [];
		}
		return $this;
	}

	/**
	 * @param array $items
	 * @param bool $update
	 * @return $this
	 */
	public function merge( array $items, $update = false )
	{
		if( ! count($this->items) )
		{
			$this->items = $items;
		}
		else if( $update )
		{
			$this->items = array_merge($this->items, $items);
		}
		else
		{
			foreach($items as $key => $value)
			{
				if( ! $this->offsetExists($key) )
				{
					$this->items[$key] = $value;
				}
			}
		}

		return $this;
	}

	/**
	 * @return string
	 */
	public function getFilename(): string
	{
		return $this->filename;
	}

	/**
	 * @return string
	 */
	public function getPathname(): string
	{
		return $this->pathname;
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @return bool
	 */
	public function fileExists(): bool
	{
		return file_exists($this->pathname) && is_file($this->pathname);
	}

	/**
	 * @throws FileWriteException
	 */
	public function save()
	{
		if( ! $this->writeFileExport($this->pathname, $this->getAll()) )
		{
			throw new FileWriteException("Can't update '{$this->filename}' config file");
		}
	}
}