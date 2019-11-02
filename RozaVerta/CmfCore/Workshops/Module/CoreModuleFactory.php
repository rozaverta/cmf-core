<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.10.2019
 * Time: 2:17
 */

namespace RozaVerta\CmfCore\Workshops\Module;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use ReflectionClass;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Scheme\TableDataLoader;
use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use RozaVerta\CmfCore\Exceptions\JsonParseException;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Filesystem\Config;
use RozaVerta\CmfCore\Filesystem\Exceptions\FileWriteException;
use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Helper\Json;
use RozaVerta\CmfCore\Log\Interfaces\LoggableInterface;
use RozaVerta\CmfCore\Log\Traits\LoggableTrait;
use RozaVerta\CmfCore\Manifest;
use RozaVerta\CmfCore\Support\Text;
use RozaVerta\CmfCore\Traits\ServiceTrait;

/**
 * Class CoreModuleFactory
 *
 * @package RozaVerta\CmfCore\Workshops\Module
 */
class CoreModuleFactory implements LoggableInterface
{
	use LoggableTrait;
	use ServiceTrait;

	private $coreResourcePath;

	private $onBeforeInstall = [];

	private $onAfterInstall = [];

	/**
	 * @param \Closure $callback
	 *
	 * @return $this
	 */
	public function onBeforeInstall( \Closure $callback )
	{
		$this->onBeforeInstall[] = $callback;
		return $this;
	}

	/**
	 * @param \Closure $callback
	 *
	 * @return $this
	 */
	public function onAfterInstall( \Closure $callback )
	{
		$this->onAfterInstall[] = $callback;
		return $this;
	}

	/**
	 * Install core module.
	 *
	 * @throws \Throwable
	 */
	public function install()
	{
		if( self::app()->installed() )
		{
			throw new InvalidArgumentException( "Core module already installed." );
		}

		if( !self::service( "host" )->isDefined() )
		{
			throw new InvalidArgumentException( "Host is not selected." );
		}

		$system = new Config( "system" );
		$system->reload();
		$status = $system->get( "status", "" );
		if( strpos( $status, "-progress" ) > 0 || $status === "progress" )
		{
			throw new InvalidArgumentException( "Warning! A system installation or update is in progress, please wait." );
		}

		try
		{
			$system
				->set( "status", "install-progress" )
				->save();
		} catch( FileWriteException $e )
		{
			throw new InvalidArgumentException( $e->getMessage(), $e );
		}

		$complete = false;

		register_shutdown_function( function() use ( $system, & $complete ) {
			if( $complete )
			{
				return;
			}

			$complete = true;

			$system->reload();
			if( $system->get( "status" ) === "install-progress" )
			{
				$system
					->set( "status", "failure" )
					->save();
			}
		} );

		$this->dispatch( $this->onBeforeInstall );

		$ref = new ReflectionClass( Manifest::class );
		$file = $ref->getFileName();
		$path = realpath( dirname( $file ) );
		if( !$path )
		{
			throw new InvalidArgumentException( "Cannot read core directory name." );
		}

		$this->coreResourcePath = $path . DIRECTORY_SEPARATOR . "resources" . DIRECTORY_SEPARATOR;
		$manifestData = $this->getResourceData( "manifest", "module" );

		$moduleNamespace = $ref->getNamespaceName() . "\\";
		$moduleName = $manifestData["name"] ?? "Core";
		$moduleVersion = $manifestData["version"] ?? "1.0";
		$moduleDatabaseTables = Arr::wrap( $manifestData["databaseTables"] ?? [] );
		$moduleDatabaseRequiredTables = [ "scheme_tables", "modules", "events" ];

		foreach( $moduleDatabaseRequiredTables as $tableName )
		{
			if( !in_array( $tableName, $moduleDatabaseTables, true ) )
			{
				throw new InvalidArgumentException( "Invalid core manifest databases list." );
			}
		}

		try
		{
			$db = self::service( "database" )->getConnection( "default" );
			if( !$db->ping() )
			{
				throw new DBALException( "Database connection ping failed." );
			}
		} catch( DBALException | NotFoundException $e )
		{
			throw new InvalidArgumentException( "Error database connection: " . $e->getMessage() );
		}

		$tablePrefix = $db->getTablePrefix();
		$originalTables = $db->getDbalConnection()->getSchemaManager()->listTableNames();
		$tables = [];
		foreach( $moduleDatabaseTables as $tableName )
		{
			$fullTable = $tablePrefix . $tableName;
			if( in_array( $fullTable, $originalTables, true ) )
			{
				throw new InvalidArgumentException( "The \"{$fullTable}\" table already exists in database." );
			}
			$tables[$tableName] = $this->getTableData( $tableName, $fullTable );
		}

		$module = [
			"id" => 1,
			"name" => $moduleName,
			"namespace_name" => $moduleNamespace,
			"version" => $moduleVersion,
			"install" => true,
		];

		$events = $this->getEvents();

		$db->transactional( function( Connection $connection ) use ( $module, $tables, $events ) {

			// 1. create "scheme_tables" table
			// 2. create "modules" table + add record to table_schema
			// 3. add Core module
			// 4. create other tables
			// 5. add events

			$this->createTable( $connection, $tables["scheme_tables"], $module["version"] );
			$this->createTable( $connection, $tables["modules"], $module["version"] );

			$connection
				->plainBuilder()
				->from( "modules" )
				->insert( $module, [
					"id" => ParameterType::INTEGER,
					"name" => ParameterType::STRING,
					"namespace_name" => ParameterType::STRING,
					"version" => ParameterType::STRING,
					"install" => ParameterType::BOOLEAN,
				] );

			$this->addDebug( "Add \"" . $module["name"] . "\" module record." );

			unset( $tables["scheme_tables"] );
			unset( $tables["modules"] );

			foreach( $tables as $table )
			{
				$this->createTable( $connection, $table, $module["version"] );
			}

			$eventBuilder = $connection
				->plainBuilder()
				->from( "events" );

			foreach( $events as $event )
			{
				$eventBuilder->insert( $event, [
					"name" => ParameterType::STRING,
					"title" => ParameterType::STRING,
					"module_id" => ParameterType::INTEGER,
					"completable" => ParameterType::BOOLEAN,
				] );
			}
		} );

		$complete = true;

		try
		{
			if( isset( $manifestData["build"] ) )
			{
				$system->set( "build", $manifestData["build"] );
			}
			else
			{
				$system->forget( "build" );
			}

			$system
				->set( "name", $manifestData["title"] ?? $moduleName )
				->set( "version", $moduleVersion )
				->set( "install", true )
				->set( "status", "install" )
				->save();
		} catch( FileWriteException $e )
		{
			$this
				->addError( $e->getMessage() )
				->addAlert( "Attention! Failed to update the system configuration file. You must update the data manually." );
		}

		$this->addDebug( "Core module successfully installed." );

		$this->dispatch( $this->onAfterInstall );
	}

	/**
	 * @param Connection $connection
	 * @param            $table
	 * @param            $version
	 *
	 * @throws DBALException
	 */
	private function createTable( Connection $connection, $table, $version ): void
	{
		// create table

		$schema = new Schema();
		$this->loadDbalTable( $schema->createTable( $table["fullTableName"] ), $table["loader"], $connection );
		$queries = $schema->toSql( $connection->getDbalDatabasePlatform() );

		foreach( $queries as $query )
		{
			$connection->exec( $query );
		}

		// add record

		$connection
			->plainBuilder()
			->from( "scheme_tables" )
			->insert( [
				"name" => $table["tableName"],
				"title" => $table["title"],
				"description" => $table["description"],
				"module_id" => 1,
				"addon" => false,
				"version" => $version,
			], [
				"name" => ParameterType::STRING,
				"title" => ParameterType::STRING,
				"description" => ParameterType::STRING,
				"module_id" => ParameterType::INTEGER,
				"addon" => ParameterType::BOOLEAN,
				"version" => ParameterType::STRING,
			] );

		$this->addDebug( Text::text( "Add new database table \"%s\".", $table["tableName"] ) );
	}

	/**
	 * @param string $tableName
	 * @param string $fullTableName
	 *
	 * @return array
	 */
	private function getTableData( string $tableName, string $fullTableName ): array
	{
		$resourceData = $this->getResourceData( "db_{$tableName}", "database_table" );

		$loader = new TableDataLoader(
			$tableName,
			isset( $resourceData["columns"] ) ? Arr::wrap( $resourceData["columns"] ) : [],
			isset( $resourceData["indexes"] ) ? Arr::wrap( $resourceData["indexes"] ) : [],
			isset( $resourceData["primaryKey"] ) ? Arr::wrap( $resourceData["primaryKey"] ) : [],
			isset( $resourceData["foreignKeys"] ) ? Arr::wrap( $resourceData["foreignKeys"] ) : [],
			isset( $resourceData["options"] ) ? Arr::wrap( $resourceData["options"] ) : [],
			isset( $resourceData["extra"] ) ? Arr::wrap( $resourceData["extra"] ) : []
		);

		return [
			"tableName" => $tableName,
			"fullTableName" => $fullTableName,
			"loader" => $loader,
			"title" => $resourceData["title"] ?? "Table {$tableName}",
			"description" => $resourceData["description"] ?? "",
		];
	}


	/**
	 * Get Doctrine DBAL Table Schema object
	 *
	 * @param Table           $queryTable
	 * @param TableDataLoader $loader
	 * @param Connection      $connection
	 *
	 * @return void
	 */
	protected function loadDbalTable( Table $queryTable, TableDataLoader $loader, Connection $connection ): void
	{
		$primary = [];

		// columns
		foreach( $loader->getColumns() as $column )
		{
			$type = $column->getType();
			if( !Type::hasType( $type ) )
			{
				$type = Type::STRING;
			}

			$options = [];

			if( !$column->isNotNull() ) $options["notnull"] = false;
			if( $column->isUnsigned() ) $options["unsigned"] = true;
			if( $column->isAutoIncrement() ) $options["autoincrement"] = true;
			if( $column->isFixed() ) $options["fixed"] = true;
			if( $column->isDefault() ) $options["default"] = $connection->convertToDatabaseValue( $column->getDefault(), $column->getType() );
			if( $column->getLength() > 0 ) $options["length"] = $column->getLength();
			if( $column->getPrecision() > 0 ) $options["precision"] = $column->getPrecision();
			if( $column->getScale() > 0 ) $options["scale"] = $column->getScale();
			if( $column->isComment() ) $options["comment"] = $column->getComment();

			$queryTable->addColumn( $column->getName(), $type, $options );
		}

		// indexes
		foreach( $loader->getIndexes() as $index )
		{
			$columns = $index->getColumns();
			$name = $index->getName();

			if( $index->isPrimary() )
			{
				$queryTable->setPrimaryKey( $columns, $name );
			}
			else if( $index->isUnique() )
			{
				$queryTable->addUniqueIndex( $columns, $name );
			}
			else
			{
				$type = $index->getType();
				$queryTable->addIndex(
					$columns,
					$name,
					$type === "FULLTEXT" || $type === "SPATIAL" ? [ strtolower( $type ) ] : []
				);
			}
		}

		// add primary key
		if( count( $primary ) )
		{
			$queryTable->setPrimaryKey( $primary );
		}

		// assign a foreign keys constraint to the table
		foreach( $loader->getForeignKeyConstraints() as $key )
		{
			$options = [];
			foreach( [ "onUpdate", "onDelete" ] as $event )
			{
				$option = $key->{$event}();
				if( $option )
				{
					$options[$event] = $option;
				}
			}

			$queryTable->addForeignKeyConstraint(
				$key->getForeignTableName(),
				$key->getColumns(),
				$key->getForeignColumns(), $options, $key->getName()
			);
		}
	}

	/**
	 * @return array
	 */
	private function getEvents(): array
	{
		$data = $this->getResourceData( "events", "event_collection" );
		$items = isset( $data["items"] ) ? Arr::wrap( $data["items"] ) : [];
		$events = [];

		foreach( $items as $event )
		{
			if( !is_array( $event ) )
			{
				$event = [ "name" => (string) $event ];
			}

			$name = $event["name"] ?? "";
			$title = $event["title"] ?? "";

			if( !strlen( $title ) )
			{
				$title = "The {$name} event.";
			}

			$events[] = [
				"name" => $name,
				"title" => $title,
				"completable" => isset( $event["completable"] ) ? (bool) $event["completable"] : false,
				"module_id" => 1,
			];
		}

		return $events;
	}

	/**
	 * @param string $name
	 * @param string $type
	 *
	 * @return array
	 */
	private function getResourceData( string $name, string $type ): array
	{
		$path = $this->coreResourcePath . $name . ".json";
		if( !file_exists( $path ) )
		{
			throw new InvalidArgumentException( "The \"{$name}\" resource file for core module not found." );
		}

		$raw = @ file_get_contents( $path );
		if( !$raw )
		{
			throw new InvalidArgumentException( "Cannot read core module \"{$name}\" file." );
		}

		try
		{
			$data = Json::parse( $raw, true );
			if( !is_array( $data ) )
			{
				throw new JsonParseException( "The \"{$name}\" resource data is not array." );
			}
		} catch( JsonParseException $e )
		{
			throw new InvalidArgumentException( "Cannot read core \"{$name}\" file. JSON parser error: " . $e->getCode() );
		}

		$type = "#/{$type}";
		$dataType = $data["type"] ?? null;
		if( $dataType !== $type )
		{
			throw new InvalidArgumentException( "Invalid core \"{$name}\" resource type." );
		}

		return $data;
	}

	/**
	 * @param array $callbacks
	 */
	private function dispatch( array & $callbacks )
	{
		foreach( $callbacks as $callback )
		{
			$callback();
		}

		// clear callbacks

		$callbacks = [];
	}
}
