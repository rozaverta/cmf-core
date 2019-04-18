<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:54
 */

namespace RozaVerta\CmfCore\Cache\File;

use RozaVerta\CmfCore\Cache\Hash;
use RozaVerta\CmfCore\Cache\Driver;
use RozaVerta\CmfCore\Filesystem\Filesystem;
use RozaVerta\CmfCore\Filesystem\Traits\WriteFileTrait;

class FileDriver extends Driver
{
	use WriteFileTrait;

	/**
	 * @var Filesystem
	 */
	private $filesystem;

	private $file_path;

	private $file_exists = false;

	public function __construct( Filesystem $filesystem, Hash $hash, string $directory = "cache" )
	{
		if( ! $hash instanceof FileHash )
		{
			throw new \InvalidArgumentException("You must used the " . FileHash::class . ' object instance for the ' . __CLASS__ . ' constructor');
		}

		parent::__construct($hash);

		$this->filesystem = $filesystem;
		$this->file_path = (defined("APP_PATH") ? APP_PATH : sys_get_temp_dir()) . $directory . DIRECTORY_SEPARATOR . $hash->getHash();
	}

	public function load( int $life = 0 )
	{
		parent::load($life);
		$this->ready();
	}

	public function has(): bool
	{
		return $this->file_exists;
	}

	public function set( string $value ): bool
	{
		if( ! defined("APP_PATH") )
		{
			return false;
		}

		$value = str_replace( '?>', '', $value);
		$value = str_replace( '<?', '', $value);
		$value = '<' . "?php defined('CMF_CORE') || exit('Not access'); ob_start(); ?" . '>' . $value . '<' . "?php \$data = ob_get_contents(); ob_end_clean();";

		if( ! $this->writeFile($this->file_path, $value) )
		{
			return false;
		}

		$this->ready(false);
		return true;
	}

	protected function exportData( $data ): bool
	{
		if( ! defined("APP_PATH") || ! $this->writeFileExport($this->file_path, $data) )
		{
			return false;
		}

		$this->ready(false);
		return true;
	}

	public function get()
	{
		return $this->has() ? $this->filesystem->getRequireData($this->file_path, "") : null;
	}

	public function import()
	{
		return $this->has() ? $this->filesystem->getRequireData($this->file_path, []) : null;
	}

	public function forget(): bool
	{
		$this->file_exists = false;

		if( $this->filesystem->isFile($this->file_path) )
		{
			return $this->filesystem->deleteOnce($this->file_path);
		}
		else
		{
			return true;
		}
	}

	protected function ready( $expired = true )
	{
		$this->file_exists = $this->filesystem->isFile($this->file_path);
		if($this->file_exists && $expired && $this->life > 0)
		{
			$time = $this->filesystem->lastModified($this->file_path);
			if( $time + $this->life < time() )
			{
				$this->forget();
			}
		}
	}
}