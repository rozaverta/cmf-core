<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 12:42
 */

namespace RozaVerta\CmfCore\Workshops\Module;

use InvalidArgumentException;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Module\ModuleManifest;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Support\Text;
use RozaVerta\CmfCore\Support\Workshop;

class ModuleRegister extends Workshop
{
	/**
	 * @var ModuleManifest
	 */
	private $config;

	/**
	 * @var string
	 */
	private $namespaceName;

	/** @noinspection all */
	/**
	 * ModuleRegister constructor.
	 *
	 * @param string $namespaceName
	 */
	public function __construct( string $namespaceName )
	{
		$this->namespaceName = trim($namespaceName, "\\") . "\\";
	}

	public function getModuleConfig(): ModuleManifest
	{
		if( ! isset($this->config) )
		{
			$className = $this->getNamespaceName() . "Manifest";
			$this->config = new $className();
		}

		return $this->config;
	}

	/**
	 * @return string
	 */
	public function getNamespaceName(): string
	{
		return $this->namespaceName;
	}

	/**
	 * @return ModuleRegister
	 *
	 * @throws NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \Throwable
	 */
	public function register()
	{
		$config = $this->getModuleConfig();
		$moduleName = $config->getName();

		// check module registered

		$num = $this
				->db
				->table(Modules_SchemeDesigner::getTableName())
				->where("name", $moduleName)
				->count("id") > 0;

		if( $num > 0 )
		{
			throw new InvalidArgumentException("Module '{$moduleName}' is already registered");
		}

		$event = new Events\RegisterModuleEvent($this, $moduleName, $this->namespaceName, $config);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if($event->isPropagationStopped())
		{
			throw new EventAbortException("Aborted the registration of a namespace for the module '{$config->getName()}'" );
		}

		$this
			->db
			->transactional(function (Connection $conn) use ($config) {

				$conn
					->table(Modules_SchemeDesigner::getTableName())
					->insert([
						"name" => $config->getName(),
						"namespace_name" => $config->getNamespaceName(),
						"install" => false,
						"version" => $config->getVersion()
					]);

				$id = (int) $conn->lastInsertId();
				if($id < 1)
				{
					throw new InvalidArgumentException("Can not ready module identifier from database");
				}

				$this->setModule( WorkshopModuleProcessor::module($id) );
		});

		$this->addDebug(Text::text("%s Module was successfully registered from the %s namespace", $config->getName(), $this->getNamespaceName()));
		$dispatcher->complete($this->getModule());

		return $this;
	}

	/**
	 * @return $this
	 *
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function unregister()
	{
		$config = $this->getModuleConfig();
		$moduleName = $config->getName();

		/** @var Modules_SchemeDesigner $row */
		$row = $this
			->db
			->table(Modules_SchemeDesigner::class)
			->where("name", $moduleName)
			->first();

		if( ! $row )
		{
			return $this;
		}

		if( $row->isInstall() )
		{
			throw new InvalidArgumentException("You must uninstall the '{$moduleName}' module before cancellation of registration");
		}

		$id = $row->getId();
		$module = WorkshopModuleProcessor::module($id);
		$this->setModule($module);

		$event = new Events\UnregisterModuleEvent($this);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if($event->isPropagationStopped())
		{
			throw new EventAbortException("Aborted the unregistration of a namespace for the module '{$config->getName()}'" );
		}

		$this
			->db
			->table(Modules_SchemeDesigner::getTableName())
			->whereId($id)
			->delete();

		$this->unsetModule();

		$this->addDebug(Text::text("%s Module was successfully deactivated", $moduleName));
		$dispatcher->complete();

		return $this;
	}
}