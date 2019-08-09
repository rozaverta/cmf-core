<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.04.2018
 * Time: 2:49
 */

namespace RozaVerta\CmfCore\Workshops\Module;

use DateTime;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Types\Type;
use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use RozaVerta\CmfCore\Database\Query\Parameters;
use RozaVerta\CmfCore\Database\Scheme\TableLoader;
use RozaVerta\CmfCore\Event\Dispatcher;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Event\Interfaces\EventPrepareInterface;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Filesystem\Exceptions\FileWriteException;
use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Module\ModuleHelper;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Support\Text;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Workshops\Event\EventProcessor;
use RozaVerta\CmfCore\Workshops\Event\HandlerProcessor;
use RozaVerta\CmfCore\Workshops\Event\Interfaces\EventProcessorExceptionInterface;
use RozaVerta\CmfCore\Workshops\View\PackageManagerProcessor;

/**
 * Class ModuleComponent
 *
 * @package RozaVerta\CmfCore\Workshops\Module
 */
class ModuleComponent extends Workshop
{
	public const UNINSTALL_ASSETS = 1;
	public const UNINSTALL_ADDONS = 2;
	public const UNINSTALL_CONFIG = 4;
	public const UNINSTALL_VERSIONS_HISTORY = 8;
	public const UNINSTALL_PACKAGES = 16;

	use Traits\ResourceBackupTrait;

	protected $moduleData = [];

	/**
	 * @return $this
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \Throwable
	 */
	public function install()
	{
		if( $this->getModule()->isInstall() )
		{
			throw new InvalidArgumentException("The module is already installed");
		}

		$this->moduleData = [];

		$event = new Events\InstallModuleEvent($this);
		$dispatcher = $this->event->dispatcher($event->getName());

		$this->addManifestListeners($dispatcher);

		$dispatcher
			->dispatch($event, function ($result) {
				if( is_array($result) ) {
					$this->moduleData = array_merge($this->moduleData, $result);
				}
			});

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Module installation aborted." );
		}

		$this->setModuleData();

		$this->installProcess();

		$this->complete( $dispatcher, "install", "The \"%s\" module successfully installed." );

		return $this;
	}

	/**
	 * @param bool $force
	 *
	 * @return $this
	 *
	 * @throws \Throwable
	 */
	public function update( bool $force = false )
	{
		$module = $this->getModule();
		if( ! $module->isInstall() )
		{
			throw new InvalidArgumentException( "Module not installed yet." );
		}

		// check version
		$old = $this
			->db
			->plainBuilder()
			->from( Modules_SchemeDesigner::getTableName() )
			->where( "id", $module->getId() )
			->value("version");

		if( ! $old )
		{
			$old = "0";
		}

		$compare = version_compare($old, $module->getVersion());
		if( $compare > 0 )
		{
			return $this->addError( "Version Detection Error or installed module version above updated." );
		}

		if( ! $force && $compare === 0 )
		{
			return $this->addDebug( "The latest version of the module is installed." );
		}

		$this->moduleData = [];

		$event = new Events\UpdateModuleEvent($this, $force, $old);
		$dispatcher = $this
			->event
			->dispatcher( $event->getName() );

		$this->addManifestListeners($dispatcher);

		$dispatcher
			->dispatch($event, function ($result) {
				if( is_array($result) ) {
					$this->moduleData = array_merge($this->moduleData, $result);
				}
			});

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Module update aborted." );
		}

		$this->setModuleData();

		$this->updateProcess($old, $force);

		$this->complete( $dispatcher, "update", "The \"%s\" module successfully updated." );

		return $this;
	}

	public function uninstall( int $flag = 0 )
	{
		// TODO: Implement uninstall() method.
	}

	/**
	 * @param Dispatcher $dispatcher
	 * @param string     $action
	 * @param string     $debugText
	 *
	 * @throws DBALException
	 * @throws NotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	protected function complete( Dispatcher $dispatcher, string $action, string $debugText ): void
	{
		$uninstall = $action === "uninstall";
		if($uninstall)
		{
			$row = [
				"install" => false
			];
		}
		else
		{
			$row = [
				"version" => $this->getModule()->getManifest()->getVersion(),
				"install" => true
			];
		}

		$this
			->db
			->getDbalConnection()
			->update(
				$this->db->getTableName(Modules_SchemeDesigner::getTableName()),
				$row,
				[
					"id" => $this->getModuleId()
				],
				[
					"version" => Type::STRING,
					"install" => Type::BOOLEAN
				]
			);

		$moduleName = $this->getModule()->getName();
		$this->setModule(
			$uninstall ? null : ModuleHelper::workshop( $this->getModuleId() )
		);

		$dispatcher->complete($action, $uninstall ? null : $this->getModule());

		$this->addDebug(Text::text($debugText, $moduleName));

		// clean cache
		try {
			$flush = $this
				->cache
				->getStore()
				->flush();

			if($flush)
			{
				$this->addDebug( "The cache was successfully cleared." );
			}
			else
			{
				$this->addError( "Cache flush process failed." );
			}

		}
		catch( NotFoundException $e ) {
			$this->addError("Flush cache settings error: " . $e->getMessage());
		}
	}

	protected function addManifestListeners( Dispatcher $dispatcher )
	{
		$dispatcher->register(function (Dispatcher $dispatcher) {

			$module    = $this->getModule();
			$manifest  = $module->getManifest()->getManifestData();
			$listeners = isset($manifest["listeners"]) ? Arr::wrap($manifest["listeners"]) : [];

			// 0. event listeners
			if(count($listeners))
			{
				foreach($listeners as $className)
				{
					try {
						$ref = new ReflectionClass($className);
					}
					catch( ReflectionException $e ) {
						$this->addError($e->getMessage());
						continue;
					}

					if( $ref->implementsInterface(EventPrepareInterface::class) )
					{
						/** @var EventPrepareInterface $ep */
						$ep = $ref->newInstance();
						$ep->prepare($dispatcher);
					}
					else
					{
						$this->addError( Text::text( "Handler must implement \"%s\" interface.", EventPrepareInterface::class ) );
					}
				}
			}

		}, "moduleManifest:" . $this->getModuleId());
	}

	/**
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\Exception\TableNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Filesystem\Exceptions\FileReadException
	 * @throws \Throwable
	 */
	protected function installProcess()
	{
		/** @var WorkshopModuleProcessor $module */
		$module = $this->getModule();
		$manifest = new Prop( $module->getManifest()->getManifestData() );

		// check and make if not exists backup directory
		$this->resourceCacheIsWritable(true, true);

		// 1. database tables

		$tables = $manifest->getArray("databaseTables");
		if(count($tables))
		{
			$drv = new Database($module);
			$drv->addLogTransport($this);
			foreach($tables as $tableName)
			{
				$drv->createTable($tableName);
			}
			unset($drv);
		}

		// 2. database values

		$rec = $this->getResourceData("database_values", "#/database_values");
		if(count($rec))
		{
			foreach($rec as $row)
			{
				['table' => $tableName, 'values' => $values] = (array) $row;
				if( in_array($tableName, $tables, true) && is_array($values) )
				{
					$this->databaseInsertValues($tableName, $values);
				}
			}
		}

		unset($tables);

		// 3. events

		$rec = $this->getResourceData("events", "#/event_collection");
		if(count($rec))
		{
			$drv = new EventProcessor($module);
			$drv->addLogTransport($this);
			foreach($rec as $event)
			{
				if( !is_array($event) )
				{
					$event = ["name" => (string) $event];
				}

				try {
					$drv->create(
						$event["name"],
						$event["title"] ?? "",
						(bool) ($event["completable"] ?? false)
					);
				}
				catch( EventProcessorExceptionInterface $e )
				{
					$this->addError( Text::text( "Create event \"%s\" error, \"%s\".", $event["name"], $e->getMessage() ) );
				}
			}
			unset($drv);
		}

		// add link

		$rec = $manifest->getArray("handlers");
		if(count($rec))
		{
			$drv = new HandlerProcessor($module);
			$drv->addLogTransport($this);
			foreach( $rec as $className => $events )
			{
				if( is_int($className) && is_string($events) )
				{
					$className = $events;
					$events = [];
				}
				else if( ! is_array($events) )
				{
					$events = [(string) $events];
				}

				try {
					$drv->createHandler($className);
				}
				catch(EventProcessorExceptionInterface $e) {
					$this->addError($e->getMessage());
					continue;
				}

				foreach($events as $eventName)
				{
					try {
						$drv->link($eventName, $className);
					} catch( EventAbortException | EventProcessorExceptionInterface $e )
					{
						$this->addError($e->getMessage());
					}
				}
			}
			unset($drv);
		}

		// 4. plugins

		$rec = $this->getResourceData("plugins", "#/plugin_collection");
		if(count($rec))
		{
			// todo
			unset($drv);
		}

		// 5. templates

		$rec = $manifest->getArray( "packages" );
		//$rec = $this->getResourceData("templates", "#/template_collection");
		if(count($rec))
		{
			$drv = new PackageManagerProcessor( $module );
			foreach( $rec as $packageName )
			{
				try
				{
					$drv->install( $packageName );
				} catch( \Exception $e )
				{
					$this->addError( Text::text( 'Can\'t create the "%s" package: "%s"', $packageName, $e->getMessage() ) );
				}
			}
			unset($drv);
		}

		// 6. routes

		$rec = $this->getResourceData("routes", "#/module_route_collection");
		if(count($rec))
		{
			/*$router = new ModuleRouter($module);
			$router->addLogTransport($this);
			foreach($rec as $item)
			{
				if( !is_array($item))
				{
					$item = ["path" => (string) $item];
				}

				try {
					$router->add(
						isset($item["path"]) ? $item["path"] : "",
						isset($item["type"]) ? $item["type"] : null,
						isset($item["position"]) && is_int($item["position"]) ? $item["position"] : null,
						isset($item["properties"]) && is_array($item["properties"]) ? $item["properties"] : []
					);
				}
				catch(\Exception $e)
				{
					$this->addError(Text::createInstance("Can not add mount point, %s", $e->getMessage()));
				}
			}*/
			unset($drv);
		}

		// 7. file configs

		$rec = $this->getResourceData("file_configs", "#/file_config_collection");
		if(count($rec))
		{
			foreach($rec as $name => $data)
			{
				if( !is_array($data) )
				{
					$this->addError( "Invalid config file data format ({$name})." );
					continue;
				}

				$drv = new ConfigFile($name, $module);
				if( $drv->fileExists() )
				{
					$this->addError( Text::text( "Can not duplicate the \"%s\" config file of the \"%s\" module.", $name, $module->getName() ) );
				}
				else
				{
					try
					{
						$drv->addLogTransport( $this )
							->merge( $data )
							->save();
					} catch( FileWriteException $e )
					{
						$this->addError( $e->getMessage() );
					}
				}

				unset($drv);
			}
		}

		$this->resourceWriteDataCache("manifest", "#/module", $manifest->toArray());

		// todo add cache clean
	}

	/**
	 * @param string $oldVersion
	 * @param bool   $force
	 *
	 * @throws DBALException
	 * @throws \Throwable
	 */
	protected function updateProcess( string $oldVersion, bool $force )
	{
		/** @var WorkshopModuleProcessor $module */
		$module = $this->getModule();
		$moduleVersion = $module->getVersion();
		$manifest = new Prop( $module->getManifest()->getManifestData() );

		// check and make if not exists backup directory
		$this->resourceCacheIsWritable(true, true);

		// 1. database tables

		$drv = new Database($module);
		$drv->addLogTransport($this);

		$tableVersion = $drv->getTablesVersionList( Database::TABLE_SYSTEM );
		$tables = $tableVersion->keys()->toArray();
		$renameVersion = $manifest->getArray("databaseRenameTables");
		$oldest = [];

		foreach($renameVersion as $item)
		{
			$from = $item["from"] ?? null;
			$to = $item["to"] ?? null;
			$version = $item["version"] ?? "0";
			if( $from && $to )
			{
				if( $tableVersion->getIs($from) && ! $tableVersion->getIs($to) && version_compare($version, $tableVersion->get($from), ">=") )
				{
					$oldest[$to] = $from;
				}
			}
		}

		$moduleDatabaseTables = $manifest->getArray("databaseTables");
		if(count($moduleDatabaseTables))
		{
			foreach($moduleDatabaseTables as $tableName)
			{
				$update = $force || isset($oldest[$tableName]) || ! $tableVersion->getIs($tableName) || version_compare($tableVersion->get($tableName), $moduleVersion, "<");
				$currentTableName = $oldest[$tableName] ?? $tableName;
				if($update)
				{
					$drv->updateTable($currentTableName, $tableName);
				}
				else
				{
					$drv->updateTableVersion($tableName);
				}

				$index = array_search($currentTableName, $tables);
				if($index !== false)
				{
					array_splice($tables, $index, 1);
				}
			}
		}

		// remove
		if(count($tables))
		{
			foreach($tables as $tableName)
			{
				$drv->dropTable($tableName);
			}
		}

		unset($drv);

		// 2. database values

		$rec = $this->getResourceData("database_values", "#/database_values");
		if(count($rec))
		{
			foreach($rec as $row)
			{
				['table' => $tableName, 'values' => $values] = (array) $row;
				if(in_array($tableName, $moduleDatabaseTables, true) && is_array($values) && ($row["required"] ?? false) === true)
				{
					$unique = $row["unique"] ?? [];
					if( ! is_array($unique) )
					{
						$unique = is_string($unique) && strlen($unique) > 0 ? [$unique] : [];
					}

					$this->databaseUpdateValues($tableName, $values, $unique);
				}
			}
		}

		// 3. events

		$rec = $this->getResourceData( "events", "#/event_collection" );
		if( count( $rec ) )
		{
			$drv = new EventProcessor( $module );
			$drv->addLogTransport( $this );
			foreach( $rec as $event )
			{
				if( !is_array( $event ) )
				{
					$event = [ "name" => (string) $event ];
				}

				try
				{
					$drv->replace(
						$event["name"],
						$event["title"] ?? "",
						(bool) ( $event["completable"] ?? false )
					);
				} catch( EventProcessorExceptionInterface $e )
				{
					$this->addError( Text::text( "Replace event \"%s\" error, \"%s\".", $event["name"], $e->getMessage() ) );
				}
			}
			unset( $drv );
		}

		// update link

		$rec = $manifest->getArray( "handlers" );
		if( count( $rec ) )
		{
			$drv = new HandlerProcessor( $module );
			$drv->addLogTransport( $this );
			foreach( $rec as $className => $events )
			{
				if( is_int( $className ) && is_string( $events ) )
				{
					$className = $events;
					$events = [];
				}
				else if( !is_array( $events ) )
				{
					$events = [ (string) $events ];
				}

				if( !$drv->registered( $className ) )
				{
					try
					{
						$drv->createHandler( $className );
					} catch( EventProcessorExceptionInterface $e )
					{
						$this->addError( $e->getMessage() );
						continue;
					}
				}

				foreach( $events as $eventName )
				{
					try
					{
						$drv->link( $eventName, $className, null, true );
					} catch( EventAbortException | EventProcessorExceptionInterface $e )
					{
						$this->addError( $e->getMessage() );
					}
				}
			}
			unset( $drv );
		}

		// 5. templates

		$rec = $manifest->getArray( "packages" );
		if( count( $rec ) )
		{
			$drv = new PackageManagerProcessor( $module );
			foreach( $rec as $packageName )
			{
				try
				{
					$drv->update( $packageName, $force );
				} catch( \Exception $e )
				{
					$this->addError( Text::text( 'Can\'t update the "%s" package: "%s"', $packageName, $e->getMessage() ) );
				}
			}
			unset( $drv );
		}

		// 7. file configs

		$rec = $this->getResourceData( "file_configs", "#/file_config_collection" );
		if( count( $rec ) )
		{
			foreach( $rec as $name => $data )
			{
				if( !is_array( $data ) )
				{
					$this->addError( "Invalid config file data format ({$name})." );
					continue;
				}

				$drv = new ConfigFile( $name, $module );
				if( !$drv->fileExists() )
				{
					try
					{
						$drv->addLogTransport( $this )
							->merge( $data )
							->save();
					} catch( FileWriteException $e )
					{
						$this->addError( $e->getMessage() );
					}
				}

				unset( $drv );
			}
		}

		if( $manifest->get( "version" ) !== $oldVersion )
		{
			//
		}
	}

	protected function uninstallData()
	{
		//
	}

	/**
	 * @param $name
	 * @param $type
	 * @param string $key
	 * @return array|mixed
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	protected function getResourceData( $name, $type, $key = "items" )
	{
		$result = [];

		try {
			$rec = $this->getModule()->getResourceJson($name);
			if($rec->getType() !== $type)
			{
				throw new InvalidArgumentException("Invalid resource type ({$name})");
			}
			$result = $rec->getArray($key);
		}
		catch(NotFoundException $e) {}

		return $result;
	}

	/**
	 * @throws \Exception
	 */
	protected function setModuleData()
	{
		$module = $this->getModule();

		$this->moduleData["moduleId"] = $module->getId();
		$this->moduleData["moduleName"] = $module->getName();
		$this->moduleData["moduleKey"] = $module->getKey();
		$this->moduleData["moduleNamespaceName"] = $module->getNamespaceName();
		$this->moduleData["moduleTitle"] = $module->getTitle();
		$this->moduleData["moduleVersion"] = $module->getVersion();
		$this->moduleData["modulePathname"] = $module->getPathname();

		$dateTime = new DateTime();
		$platform = $this->db->getDbalDatabasePlatform();

		$this->moduleData["datetime"] = $dateTime;
		$this->moduleData["tmDate"] = $dateTime->format($platform->getDateFormatString());
		$this->moduleData["tmTime"] = $dateTime->format($platform->getTimeFormatString());
		$this->moduleData["tmDatetime"] = $dateTime->format($platform->getDateTimeFormatString());
		$this->moduleData["tmNow"] = $platform->getNowExpression();
	}

	protected function replaceModuleData( array $row )
	{
		foreach(array_keys($row) as $key)
		{
			if( is_string($row[$key]) )
			{
				$row[$key] = $this->replaceModuleDataText($row[$key]);
			}
		}

		return $row;
	}

	protected function replaceModuleDataText( string $string ): string
	{
		$pos = strpos($string, '${');

		if( $pos !== false )
		{
			$len = strlen($string);

			// raw $value = "${var_name}"
			if($pos === 0 && $string[$len-1] === "}")
			{
				$name = substr($string, 2, $len-3);
				return $this->moduleData[$name] ?? $string;
			}

			// string "{var_name} ... text {var_name2}"
			return preg_replace_callback('/\$\{([a-z0-9_]+)\}/', function($m) {
				return isset($this->moduleData[$m[1]]) ? (string) $this->moduleData[$m[1]] : $m[0];
			}, $string);
		}

		return $string;
	}

	protected function databaseValuesTypes( string $table ): ?array
	{
		try
		{
			$loader = new TableLoader( $table, $this->getModule() );
		} catch( \Throwable $e )
		{
			$this->addError( "Database insert error. " . $e->getMessage() );
			return null;
		}

		$types = [];
		foreach( $loader->getColumns() as $column )
		{
			$types[$column->getName()] = $column->getType();
		}

		return $types;
	}

	protected function databaseFilterValuesTypes( array $values, array $types )
	{
		$result = [];
		foreach( array_keys( $values ) as $key )
		{
			$result[$key] = isset( $types[$key] ) ? $types[$key] : Parameters::inferType( $values[$key] );
		}
		return $result;
	}

	protected function databaseInsertValues( string $tableName, array $values )
	{
		$types = $this->databaseValuesTypes( $tableName );
		if( is_null( $types ) )
		{
			return;
		}

		$build = $this
			->db
			->plainBuilder()
			->from( $tableName );

		if( Arr::associative($values) )
		{
			$values = [$values];
		}

		foreach($values as $insert)
		{
			$insert = $this->replaceModuleData($insert);
			try {
				$build->insert( $insert, $this->databaseFilterValuesTypes( $insert, $types ) );
			}
			catch(DBALException $e)
			{
				$this->addError( Text::text( "Database insert error. Table \"%s\", error - \"%s\".", $tableName, $e->getMessage() ) );
			}
		}
	}

	protected function databaseUpdateValues(string $tableName, array $values, array $unique)
	{
		$types = $this->databaseValuesTypes( $tableName );
		if( is_null( $types ) )
		{
			return;
		}

		if( Arr::associative($values) )
		{
			$values = [$values];
		}

		$ur = count($unique);
		foreach($values as $insert)
		{
			$u = $ur ? $unique : array_keys($insert);
			if(!count($u))
			{
				continue;
			}

			try {
				$builder = $this
					->db
					->plainBuilder()
					->from( $tableName );

				foreach($u as $name)
				{
					if( ! isset($insert[$name]))
					{
						continue 2;
					}
					else
					{
						$builder->where($name, $insert[$name]);
					}
				}

				if($builder->count() > 0)
				{
					continue;
				}
				else
				{
					$builder
						->removePart( "where" )
						->insert( $insert, $this->databaseFilterValuesTypes( $insert, $types ) );
				}
			}
			catch(DBALException $e)
			{
				$this->addError( Text::text( "Database insert error. Table \"%s\", error - \"%s\".", $tableName, $e->getMessage() ) );
			}
		}
	}
}