<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.08.2018
 * Time: 11:16
 */

namespace RozaVerta\CmfCore\Workshops\Module;

use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Filesystem\Exceptions\FileWriteException;
use RozaVerta\CmfCore\Filesystem\Traits\WriteFileTrait;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Traits\GetTrait;
use RozaVerta\CmfCore\Traits\SetTrait;

class ConfigFile extends Workshop
{
	use WriteFileTrait;
	use SetTrait;
	use GetTrait;

	protected $items = [];

	protected $filename;

	protected $pathname;

	protected $path;

	protected $ready = null;

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
	 * @return void
	 *
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 */
	public function save(): void
	{
		$event = new Events\SaveConfigFileEvent($this);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( sprintf("Aborted the write action for the '%s' config file of the '%s' module", $this->filename, $this->getModule()->getName()) );
		}

		if($this->writeFileExport($this->pathname, $this->getAll()))
		{
			throw new FileWriteException( sprintf("Can't %s '%s' config file", $this->fileExists() ? "update" : "create", $this->filename) );
		}

		$dispatcher->complete();
	}
}