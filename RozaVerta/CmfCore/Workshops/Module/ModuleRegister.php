<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 12:42
 */

namespace RozaVerta\CmfCore\Workshops\Module;

use InvalidArgumentException;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Module\ModuleHelper;
use RozaVerta\CmfCore\Module\ModuleManifest;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Support\Text;
use RozaVerta\CmfCore\Support\Workshop;

/**
 * Class ModuleRegister
 *
 * @package RozaVerta\CmfCore\Workshops\Module
 */
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

	/** @noinspection PhpMissingParentConstructorInspection */
	/**
	 * ModuleRegister constructor.
	 *
	 * @param string $namespaceName
	 *
	 * @throws NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\ClassNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function __construct( string $namespaceName )
	{
		$this->namespaceName = trim($namespaceName, "\\") . "\\";
		$this->thisServices();
	}

	/**
	 * Get module manifest
	 *
	 * @return ModuleManifest
	 */
	public function getModuleManifest(): ModuleManifest
	{
		if( ! isset($this->config) )
		{
			$className = $this->getNamespaceName() . "Manifest";
			$this->config = new $className();
		}

		return $this->config;
	}

	/**
	 * Get module namespace name
	 *
	 * @return string
	 */
	public function getNamespaceName(): string
	{
		return $this->namespaceName;
	}

	/**
	 * The module has been registered (added to the database).
	 *
	 * @param null $moduleId
	 *
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function registered( & $moduleId = null ): bool
	{
		return ModuleHelper::exists( $this->namespaceName, $moduleId );
	}

	/**
	 * Register a new module (add an entry to the database).
	 *
	 * @return ModuleRegister
	 *
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function register()
	{
		$manifest = $this->getModuleManifest();
		$moduleName = $manifest->getName();

		// check module registered
		if( $this->registered() )
		{
			throw new InvalidArgumentException( "Module \"{$moduleName}\" is already registered." );
		}

		$event = new Events\RegisterModuleEvent( $this, $moduleName, $this->namespaceName, $manifest );
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if($event->isPropagationStopped())
		{
			throw new EventAbortException( "Aborted the registration of a namespace for the module \"{$manifest->getName()}\"." );
		}

		$this
			->db
			->transactional( function( Connection $conn ) use ( $manifest ) {

				$id = (int) $conn
					->builder( Modules_SchemeDesigner::getTableName() )
					->insertGetId( [
						"name" => $manifest->getName(),
						"namespace_name" => $manifest->getNamespaceName(),
						"install" => false,
						"version" => $manifest->getVersion(),
					]);

				if($id < 1)
				{
					throw new InvalidArgumentException( "Can not ready module identifier from database." );
				}

				$this->setModule( WorkshopModuleProcessor::module($id) );
		});

		$this->addDebug( Text::text( '"%s" Module was successfully registered from the "%s" namespace.', $manifest->getName(), $this->getNamespaceName() ) );
		$dispatcher->complete($this->getModule());

		return $this;
	}

	/**
	 * Unregister the module (delete the record from the database).
	 *
	 * @return $this
	 *
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function unregister()
	{
		$manifest = $this->getModuleManifest();
		$moduleName = $manifest->getName();

		/** @var Modules_SchemeDesigner $row */
		$row = Modules_SchemeDesigner::find()
			->where( "name", $moduleName )
			->first();

		if( ! $row )
		{
			return $this;
		}

		if( $row->isInstall() )
		{
			throw new InvalidArgumentException( "You must uninstall the \"{$moduleName}\" module before cancellation of registration." );
		}

		$id = $row->getId();
		$module = WorkshopModuleProcessor::module($id);
		$this->setModule($module);

		$event = new Events\UnregisterModuleEvent($this);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if($event->isPropagationStopped())
		{
			throw new EventAbortException( "Aborted the unregistration of a namespace for the module \"{$manifest->getName()}\"." );
		}

		$this
			->db
			->builder( Modules_SchemeDesigner::getTableName() )
			->whereId($id)
			->delete();

		$this->unsetModule();

		$this->addDebug( Text::text( '"%s" Module was successfully deactivated.', $moduleName ) );
		$dispatcher->complete();

		return $this;
	}
}