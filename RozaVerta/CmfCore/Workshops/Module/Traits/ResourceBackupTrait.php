<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.04.2018
 * Time: 0:19
 */

namespace RozaVerta\CmfCore\Workshops\Module\Traits;

use InvalidArgumentException;
use RozaVerta\CmfCore\Filesystem\Exceptions\PathInvalidArgumentException;
use RozaVerta\CmfCore\Filesystem\Filesystem;
use RozaVerta\CmfCore\Filesystem\Iterator;
use RozaVerta\CmfCore\Filesystem\Traits\WriteFileTrait;
use RozaVerta\CmfCore\Helper\Json;
use RozaVerta\CmfCore\Log\Interfaces\LoggableInterface;
use RozaVerta\CmfCore\Log\LogManager;
use RozaVerta\CmfCore\Module\Module;
use RozaVerta\CmfCore\Module\ResourceJson;
use RozaVerta\CmfCore\Support\Text;

trait ResourceBackupTrait
{
	use WriteFileTrait;

	/**
	 * Get module
	 *
	 * @return Module
	 */
	abstract public function getModule(): Module;

	/**
	 * Get module id
	 *
	 * @return int
	 */
	abstract public function getModuleId(): int;

	protected function resourceRemoveCache( $name, string $type, bool $recursive = false )
	{
		$name = $this->getResourceFileName($name, $type);

		if($recursive)
		{
			try {
				/** @var \SplFileInfo $directory */
				foreach( Iterator::iterator($this->getResourcePath(true))->getDirectories() as $directory)
				{
					$file = $directory->getRealPath() . DIRECTORY_SEPARATOR . $name;
					if(file_exists($file) && is_file($file))
					{
						@ unlink($file) || LogManager::getInstance()->lastPhp();
					}
				}
			}
			catch(PathInvalidArgumentException $e) {
				LogManager::getInstance()->lastPhp();
			}
		}
		else
		{
			$file = $this->getResourcePath(true, true) . $name;
			if(file_exists($file) && is_file($file))
			{
				@ unlink($file) || LogManager::getInstance()->lastPhp();
			}
		}
	}

	/**
	 * @param bool $version
	 * @param bool $throw
	 * @return bool
	 */
	protected function resourceCacheIsWritable( $version = false, $throw = false ): bool
	{
		$fs = Filesystem::getInstance();

		$path = rtrim($this->getResourcePath(true), DIRECTORY_SEPARATOR);
		$test = $fs->makeDirectory($path) && $fs->isWritable($path);

		if($test && $version)
		{
			$path .= DIRECTORY_SEPARATOR . "v_" . $this->getModule()->getVersion();
			$test  = $fs->makeDirectory($path) && $fs->isWritable($path);
		}

		if($test)
		{
			return true;
		}

		if($throw)
		{
			throw new InvalidArgumentException("Can not create the resource version file, the directory is not writable");
		}

		return false;
	}

	protected function resourceWriteCache( ResourceJson $resource ): bool
	{
		$text = $resource->getRawData();
		$name = $this->getResourceFileName($resource->getName(), $resource->getType());

		if( $this->writeFile($this->getResourcePath(true, true) . $name, $text, false) )
		{
			return true;
		}

		if( $this instanceof LoggableInterface )
		{
			$this->addDebug(Text::text("Failure backup file %s", $name));
		}

		return false;
	}

	protected function resourceWriteDataCache( string $name, string $type, array $data ): bool
	{
		$text = Json::stringify($data);
		$name = $this->getResourceFileName($name, $type);

		if( $this->writeFile($this->getResourcePath(true, true) . $name, $text, false) )
		{
			return true;
		}

		if( $this instanceof LoggableInterface )
		{
			$this->addDebug(Text::text("Failure backup file %s", $name));
		}

		return false;
	}

	private function getResourceFileName( $name, $type )
	{
		if( $type === "#/database_table" && substr($name, 0, 3) !== "db_" )
		{
			$name = "db_" . $name;
		}

		$dot = stripos($name, ".");
		if($dot === false || substr($name, $dot) !== ".json")
		{
			$name .= ".json";
		}

		return $name;
	}
	
	private function getResourcePath( $module = false, $version = false )
	{
		$path = APP_PATH . "resources" . DIRECTORY_SEPARATOR;

		if($module)
		{
			$path .= $this->getModuleId() . DIRECTORY_SEPARATOR;
			if($version)
			{
				$path .= "v_" . $this->getModule()->getVersion() . DIRECTORY_SEPARATOR;
			}
		}

		return $path;
	}
}