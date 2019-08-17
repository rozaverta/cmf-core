<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.08.2018
 * Time: 11:16
 */

namespace RozaVerta\CmfCore\Workshops\Module;

use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Filesystem\Exceptions\FileWriteException;
use RozaVerta\CmfCore\Filesystem\Traits\WriteFileTrait;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Interfaces\SetterAndGetter;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Traits\GetTrait;
use RozaVerta\CmfCore\Traits\MergeTrait;
use RozaVerta\CmfCore\Traits\SetTrait;

/**
 * Class ConfigFile
 *
 * @package RozaVerta\CmfCore\Workshops\Module
 */
class ConfigFile extends Workshop implements SetterAndGetter
{
	use WriteFileTrait;
	use SetTrait;
	use GetTrait;
	use MergeTrait;

	protected $items = [];

	protected $filename;

	protected $pathname;

	protected $path;

	protected $ready = null;

	/**
	 * ConfigFile constructor.
	 *
	 * @param string                  $name
	 * @param WorkshopModuleProcessor $module
	 *
	 * @throws \RozaVerta\CmfCore\Exceptions\ClassNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function __construct( string $name, WorkshopModuleProcessor $module )
	{
		parent::__construct($module);

		if( preg_match('/\.php$/i', $name) )
		{
			$name = substr($name, 0, strlen($name) - 4);
		}

		$this->filename = $name;
		$this->path = Path::config($module->getKey());
		$this->pathname = $this->path . $this->filename . ".php";
	}

	/**
	 * Reload data from config file.
	 *
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
	 * Get config filename.
	 *
	 * @return string
	 */
	public function getFilename(): string
	{
		return $this->filename;
	}

	/**
	 * Get config pathname.
	 *
	 * @return string
	 */
	public function getPathname(): string
	{
		return $this->pathname;
	}

	/**
	 * Get config path.
	 *
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * Configuration file exists.
	 *
	 * @return bool
	 */
	public function fileExists(): bool
	{
		return file_exists($this->pathname) && is_file($this->pathname);
	}

	/**
	 * Save the configuration file. Create or overwrite file contents data.
	 *
	 * @return void
	 *
	 * @throws EventAbortException
	 * @throws FileWriteException
	 * @throws \Throwable
	 */
	public function save(): void
	{
		$event = new Events\SaveConfigFileEvent($this);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( sprintf( 'Aborted the write action for the "%s" config file of the "%s" module.', $this->filename, $this->getModule()->getName() ) );
		}

		if($this->writeFileExport($this->pathname, $this->getAll()))
		{
			throw new FileWriteException( sprintf( 'Can\'t %s "%s" config file.', $this->fileExists() ? "update" : "create", $this->filename ) );
		}

		$dispatcher->complete();
	}
}