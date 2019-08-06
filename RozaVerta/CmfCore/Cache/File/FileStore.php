<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:16
 */

namespace RozaVerta\CmfCore\Cache\File;

use RozaVerta\CmfCore\Cache\Interfaces\CacheDriverInterface;
use RozaVerta\CmfCore\Cache\Properties\Property;
use RozaVerta\CmfCore\Cache\Properties\PropertyMemory;
use RozaVerta\CmfCore\Cache\Properties\PropertyStats;
use RozaVerta\CmfCore\Cache\Store;
use RozaVerta\CmfCore\Filesystem\Filesystem;
use RozaVerta\CmfCore\Filesystem\Iterator;

class FileStore extends Store
{
	protected $filesystem;

	protected $directory = "cache";

	public function __construct( Filesystem $filesystem, string $store_name, string $directory = "", int $life = 0 )
	{
		parent::__construct($store_name, $life);

		$this->filesystem = $filesystem;

		$directory = trim($directory, "/");
		if( strlen($directory) )
		{
			$this->directory = $directory;
		}
	}

	public function createFactory( string $name, string $prefix = "", array $properties = [], int $life = null ): CacheDriverInterface
	{
		$value = new FileDriver($this->filesystem, new FileHash($name, $prefix, $properties), $this->directory);
		$value->load(is_null($life) ? $this->life : $life);
		return $value;
	}

	public function flush( string $prefix = null ): bool
	{
		$base_dir = APP_PATH . $this->directory;

		if( is_null($prefix) )
		{
			$prefix = trim($prefix, "/");
			if( strlen($prefix) )
			{
				if( DIRECTORY_SEPARATOR !== "/" )
				{
					$prefix = str_replace("/", DIRECTORY_SEPARATOR, $prefix);
				}
				$base_dir .= DIRECTORY_SEPARATOR . $prefix;
			}

			// fix ../../ path
			$path = realpath($base_dir);
			if( strpos($path, $base_dir) !== 0 )
			{
				throw new \InvalidArgumentException("Invalid cache prefix");
			}
		}

		// remove file
		if( $this->filesystem->isFile($base_dir) )
		{
			return $this->filesystem->delete($base_dir);
		}

		// clean directory
		if( $this->filesystem->isDirectory($base_dir) )
		{
			return $this->filesystem->deleteDirectories($base_dir);
		}

		return true;
	}

	public function info(): array
	{
		$info = [];

		$info[] = new Property("driver", "file");
		$info[] = new Property("application_directory", $this->directory);
		$info[] = new Property("full_path", APP_PATH . $this->directory);
		$info[] = new Property("default_life", $this->life);

		return $info;
	}

	public function stats(): array
	{
		$path = APP_PATH . $this->directory;

		/** @var PropertyMemory[] $memories */
		$files = 0;
		$bytes = 0;
		$memories = [];

		Iterator::iterator($path)->each(
			function(\SplFileInfo $file, $depth, $base_path) use (& $memories, & $files, & $bytes)
			{
				$is_file = $file->isFile();
				$size = $is_file ? $file->getSize() : 0;

				if( $is_file )
				{
					++ $files;
					$bytes += $size;
				}

				$prefix = substr($file->getPathname(), strlen($base_path) + 1);

				if($depth === 1)
				{
					$memories[$prefix] = new PropertyMemory($prefix, $size, $is_file ? 1 : 0);
				}
				else if( $is_file )
				{
					$slash = strpos($prefix, DIRECTORY_SEPARATOR);
					if( $slash !== false )
					{
						$prefix = substr($prefix,0, $slash);
					}

					if( isset($memories[$prefix]) )
					{
						$memories[$prefix]->add($size);
					}
					else
					{
						$memories[$prefix] = new PropertyMemory($prefix, $size,1);
					}
				}
			});

		$stats = [
			new PropertyStats("path",  $path),
			new PropertyStats("files", $files),
			new PropertyStats("bytes", $bytes)
		];

		return array_merge($stats, array_values($memories));
	}
}