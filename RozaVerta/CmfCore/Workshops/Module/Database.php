<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 07.04.2018
 * Time: 18:31
 */

namespace RozaVerta\CmfCore\Workshops\Module;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\SchemaDiff;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\ParameterType;

use InvalidArgumentException;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Filesystem\Exceptions\FileNotFoundException;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Scheme\TableLoader;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Schemes\SchemeTables_SchemeDesigner;
use RozaVerta\CmfCore\Support\Collection;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Support\Text;

/**
 * Class Database
 *
 * @package RozaVerta\CmfCore\Workshops\Module
 */
class Database extends Workshop
{
	use Traits\ResourceBackupTrait;

	public const TABLE_SYSTEM = 1;
	public const TABLE_ADDON = 2;
	public const TABLE_ALL = 3;

	/**
	 * Get module version
	 *
	 * @return string
	 */
	public function getModuleVersion()
	{
		return $this->getModule()->getVersion();
	}

	/**
	 * Create new table in database
	 *
	 * @param string $tableName
	 *
	 * @return $this
	 *
	 * @throws TableNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \Throwable
	 */
	public function createTable( string $tableName )
	{
		$design = $this->getTableSchemeDesigner($tableName);
		if($design->getId() > 0)
		{
			throw new InvalidArgumentException( "Table \"{$tableName}\" has been installed." );
		}

		$moduleId = $this->getModuleId();

		$this->resourceCacheIsWritable(false, true);

		$table = new TableLoader( $tableName, $this->getModule() );
		$fileResource = $table->getResource();
		$schema = new Schema();
		$tableDbal = $this->getDbalTable( $table, $schema->createTable( $this->getTablePrefix() . $table->getName() ) );

		// call listener
		$event = new Events\CreateDatabaseTableEvent($this, [
			"tableName" => $tableName,
			"resource" => $fileResource,
			"loader" => $table,
			"dbalSchema" => $tableDbal
		]);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Aborted the creation of a table for the table \"{$tableName}\"." );
		}

		$queries = $schema->toSql( $this->getDoctrineDbalPlatform() );
		$record = [
			"module_id" => $moduleId,
			"name" => $tableName,
			"title" => $fileResource->getIs("title") ? $fileResource->get("title") : "Table {$tableName}",
			"description" => $fileResource->getOr("description", ""),
			"version" => $this->getModuleVersion()
		];

		$this
			->db
			->transactional(function(Connection $dbConn) use ($queries, $record) {

				$conn = $dbConn->getDbalConnection();
				foreach($queries as $sql)
				{
					$conn->exec($sql);
				}

				$conn->insert(
					$dbConn->getTableName(SchemeTables_SchemeDesigner::getTableName()),
					$record, [
					"module_id" => ParameterType::INTEGER,
					"name" => ParameterType::STRING,
					"title" => ParameterType::STRING,
					"description" => ParameterType::STRING,
					"version" => ParameterType::STRING,
					]);

				// fixed core module
				if($this->getModuleId() === 1 && $record["name"] === Modules_SchemeDesigner::getTableName())
				{
					$module = $this->getModule();
					$conn->insert(
						$dbConn->getTableName($record["name"]),
						[
							"id" => $module->getId(),
							"name" => $module->getName(),
							"namespace_name" => $module->getNamespaceName(),
							"version" => $module->getVersion(),
							"install" => false
						],
						[
							"id" => ParameterType::INTEGER,
							"name" => ParameterType::STRING,
							"namespace_name" => ParameterType::STRING,
							"version" => ParameterType::STRING,
							"install" => ParameterType::BOOLEAN,
						]
					);
				}
			});

		// write resources

		$this->resourceWriteCache($fileResource);

		$dispatcher->complete();
		$this->addDebug( Text::text( "Add new database table \"%s\".", $tableName ) );

		return $this;
	}

	/**
	 * Update or rename table in database
	 *
	 * @param string $tableName
	 * @param string|null $tableRename
	 *
	 * @return $this
	 *
	 * @throws TableNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \Throwable
	 */
	public function updateTable( string $tableName, ?string $tableRename = null )
	{
		$designer = $this->getTableSchemeDesigner($tableName);
		if( $designer->getId() < 1 )
		{
			throw new InvalidArgumentException( "The \"{$tableName}\" table has been not installed." );
		}

		if( $designer->getModuleId() !== $this->getModuleId() )
		{
			throw new InvalidArgumentException( "The \"{$tableName}\" is used by another module." );
		}

		$oldestVersion = $designer->getVersion();
		if( version_compare($this->getModuleVersion(), $oldestVersion, "<") )
		{
			throw new InvalidArgumentException( "The latest version of the module can not be less than the current version of the module." );
		}

		$rename = false;
		if( ! $tableRename )
		{
			$tableRename = $tableName;
		}
		else if($tableRename !== $tableName)
		{
			$rename = true;
		}

		$this->resourceCacheIsWritable(false, true);

		try {
			$loaderOldest = new TableLoader($tableName, $this->getModule(), $designer->getVersion());
			$tableDbalOldest = $this->getDbalTable( $loaderOldest );
			$lost = false;
		}
		catch(FileNotFoundException $e) {
			$loaderOldest = null;
			$tableDbalOldest = $this->reloadDbalTableFromDatabase($tableName);
			$lost = true;
		}

		$loader = new TableLoader($tableRename, $this->getModule());
		$resource = $loader->getResource();
		$tableDbal = $this->getDbalTable($loader);

		$comparator = new Comparator();
		$diff = $comparator->diffTable($tableDbalOldest, $tableDbal);

		// call listener
		$event = new Events\UpdateDatabaseTableEvent($this, [
			"tableName" => $tableRename,
			"tableNameOldest" => $tableName,
			"loader" => $loader,
			"loaderOldest" => $loaderOldest,
			"resource" => $resource,
			"resourceOldest" => $lost ? null : $loaderOldest->getResource(),
			"dbalSchema" => $tableDbal,
			"dbalSchemaOldest" => $tableDbalOldest,
			"lost" => $lost,
			"diff" => $diff
		]);

		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Updating table \"{$tableName}\" was aborted." );
		}

		$queries = [];
		$update = [];

		if( $diff !== false )
		{
			$diffSchema = new SchemaDiff([], [$diff]);
			$queries = $diffSchema->toSql( $this->getDoctrineDbalPlatform() );
		}

		// update the resource information in the database and write the resource file to the cache
		$write = $rename || count($queries) || $lost;
		if( $write )
		{
			$update["version"] = $this->getModuleVersion();
		}

		if( $rename )
		{
			$update["name"] = $tableRename;
		}

		foreach(["title", "description"] as $key)
		{
			if($resource->getIs($key))
			{
				$value = $resource->get($key);
				if($value !== $designer->get($key))
				{
					$update[$key] = $resource->get($key);
				}
			}
		}

		if($write || count($update))
		{
			$this
				->db
				->transactional(function(Connection $conn) use ($queries, $designer, $update) {

					$tableName = $conn->getTableName(SchemeTables_SchemeDesigner::getTableName());
					$conn = $conn->getDbalConnection();
					foreach($queries as $sql)
					{
						$conn->exec($sql);
					}

					count($update) &&
					$conn->update(
							$tableName,
							$update,
							[
								"id" => $designer->getId()
							],
							[
								"module_id" => ParameterType::INTEGER,
								"name" => ParameterType::STRING,
								"title" => ParameterType::STRING,
								"description" => ParameterType::STRING,
								"version" => ParameterType::STRING,
							]);
				});

			$this->resourceWriteCache($resource);
		}

		// complete

		$dispatcher->complete();
		$this->addDebug( Text::text( "Update database table \"%s\".", $tableName ) );
		if($rename)
		{
			$this->addDebug( Text::text( "Rename database table from \"%s\" to \"%s\".", $tableName, $tableRename ) );
		}

		return $this;
	}

	/**
	 * Update table version. The table version is determined from the module version.
	 *
	 * @param string $tableName
	 *
	 * @return $this|Database
	 *
	 * @throws TableNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function updateTableVersion( string $tableName )
	{
		$designer = $this->getTableSchemeDesigner($tableName);
		if( $designer->getId() < 1 )
		{
			return $this->addError( "The \"{$tableName}\" table has been not found, cannot update version." );
		}

		if( $designer->getModuleId() !== $this->getModuleId() )
		{
			return $this->addError( "The \"{$tableName}\" table is used by another module." );
		}

		if( $designer->getVersion() < $this->getModuleVersion() )
		{
			$this
				->db
				->plainBuilder()
				->from( SchemeTables_SchemeDesigner::getTableName() )
				->limit( 1 )
				->where( "id", $designer->getId() )
				->update([
					"version" => $this->getModuleVersion()
				]);
		}

		return $this;
	}

	/**
	 * Drop table from database
	 *
	 * @param string $tableName
	 *
	 * @return $this
	 *
	 * @throws TableNotFoundException
	 * @throws \Throwable
	 */
	public function dropTable( string $tableName )
	{
		$designer = $this->getTableSchemeDesigner($tableName);
		if( ! $designer->getId() < 1 )
		{
			// table is not installed
			return $this;
		}

		if( $designer->getModuleId() !== $this->getModuleId() )
		{
			throw new InvalidArgumentException( "The \"{$tableName}\" table is used by another module." );
		}

		try {
			$loader = new TableLoader($tableName, $this->getModule(), $designer->getVersion());
			$tableDbal = $this->getDbalTable($loader);
		}
		catch(FileNotFoundException $e) {
			$loader = null;
			$tableDbal = $this->reloadDbalTableFromDatabase($tableName);
		}

		// call listener
		$event = new Events\DropDatabaseTableEvent($this, [
			"tableName" => $tableName,
			"loader" => $loader,
			"resource" => is_null($loader) ? null : $loader->getResource(),
			"dbalSchema" => $tableDbal,
			"lost" => is_null($loader)
		]);

		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Deleting table \"{$tableName}\" was aborted." );
		}

		$schema = new Schema();
		$schema->dropTable($this->getTablePrefix() . $tableName);
		$queries = $schema->toSql( $this->getDoctrineDbalPlatform() );

		$this
			->db
			->transactional(function(Connection $conn) use($queries, $designer) {

				$tableName = $conn->getTableName(SchemeTables_SchemeDesigner::getTableName());
				$conn = $conn->getDbalConnection();
				foreach($queries as $sql)
				{
					$conn->exec($sql);
				}

				$conn->delete($tableName, ["id" => $designer->getId()]);
			});

		// remove resource file
		$this->resourceRemoveCache($tableName, "#/database_table", true);

		$dispatcher->complete();
		$this->addDebug( Text::text( "Drop database table \"%s\".", $tableName ) );

		return $this;
	}

	/**
	 * Get list of current table versions from database.
	 *
	 * @param int $mode
	 *
	 * @return Collection
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function getTablesVersionList( int $mode = self::TABLE_ALL ): Collection
	{
		$builder = $this
			->db
			->plainBuilder()
			->from( SchemeTables_SchemeDesigner::getTableName() )
			->where("module_id", $this->getModuleId());

		if( $mode === self::TABLE_SYSTEM )
		{
			$builder->where( "addon", false );
		}
		else if( $mode === self::TABLE_ADDON )
		{
			$builder->where( "addon", true );
		}
		else if( $mode !== self::TABLE_ALL )
		{
			throw new InvalidArgumentException( "Invalid table mode." );
		}

		$all = $builder
			->orderBy( "name" )
			->project( function( $row ) {
				return $row["version"];
			}, [ "name", "version" ], "name" );

		return new Collection( $all );
	}

	/**
	 * Get Doctrine DBAL Table Schema object
	 *
	 * @param TableLoader $table
	 * @param Table|null $queryTable
	 *
	 * @return Table
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	protected function getDbalTable( TableLoader $table, ?Table $queryTable = null ): Table
	{
		if( is_null($queryTable) )
		{
			$queryTable = new Table( $this->getTablePrefix() . $table->getName() );
		}

		$primary = [];

		// columns
		foreach( $table->getColumns() as $column )
		{
			$type = $column->getType();
			if( ! Type::hasType($type) )
			{
				$type = Type::STRING;
			}

			$options = [];

			if(! $column->isNotNull()) $options["notnull"] = false;
			if($column->isUnsigned()) $options["unsigned"] = true;
			if($column->isAutoIncrement()) $options["autoincrement"] = true;
			if($column->isFixed()) $options["fixed"] = true;
			if($column->isDefault()) $options["default"] = $this->db->convertToDatabaseValue($column->getDefault(), $column->getType());
			if($column->getLength() > 0) $options["length"] = $column->getLength();
			if($column->getPrecision() > 0) $options["precision"] = $column->getPrecision();
			if($column->getScale() > 0) $options["scale"] = $column->getScale();
			if($column->isComment()) $options["comment"] = $column->getComment();

			$queryTable->addColumn($column->getName(), $type, $options);
		}

		// indexes
		foreach( $table->getIndexes() as $index )
		{
			$columns = $index->getColumns();
			$name = $index->getName();

			if( $index->isPrimary() )
			{
				$queryTable->setPrimaryKey($columns, $name);
			}
			else if( $index->isUnique() )
			{
				$queryTable->addUniqueIndex($columns, $name);
			}
			else
			{
				$type = $index->getType();
				$queryTable->addIndex(
					$columns,
					$name,
					$type === "FULLTEXT" || $type === "SPATIAL" ? [strtolower($type)] : []
				);
			}
		}

		// add primary key
		if(count($primary))
		{
			$queryTable->setPrimaryKey($primary);
		}

		// assign a foreign keys constraint to the table
		foreach( $table->getForeignKeyConstraints() as $key )
		{
			$options = [];
			foreach(["onUpdate", "onDelete"] as $event)
			{
				$option = $key->{$event}();
				if($option)
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

		return $queryTable;
	}

	/**
	 * Get SchemeDesigner
	 *
	 * @param string $tableName
	 *
	 * @return SchemeTables_SchemeDesigner
	 *
	 * @throws TableNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	protected function getTableSchemeDesigner( string $tableName )
	{
		try {
			$row = SchemeTables_SchemeDesigner::find()
				->where("name", $tableName)
				->first();
		}
		catch(TableNotFoundException $e) {
			if( $tableName === "scheme_tables" )
			{
				$row = false;
			}
			else
			{
				throw $e;
			}
		}

		if( !$row )
		{
			$row = new SchemeTables_SchemeDesigner([
				"id" => 0,
				"module_id" => $this->getModuleId(),
				"name" => $tableName,
				"title" => "Table " . $tableName,
				"description" => "",
				"version" => "0"
			]);
		}

		return $row;
	}

	/**
	 * Reload table schema if resource record was lost
	 *
	 * @param string $tableName
	 * @return Table
	 * @throws \Doctrine\DBAL\DBALException
	 */
	protected function reloadDbalTableFromDatabase( string $tableName ): Table
	{
		$fullTableName = $this->getTablePrefix() . $tableName;
		$sm = $this->db->getDbalConnection()->getSchemaManager();

		return new Table(
			$fullTableName,
			$sm->listTableColumns($fullTableName),
			$sm->listTableIndexes($fullTableName),
			$sm->listTableForeignKeys($fullTableName)
		);
	}

	/**
	 * Get Doctrine DBAL Platform
	 *
	 * @return AbstractPlatform
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	protected function getDoctrineDbalPlatform(): AbstractPlatform
	{
		return $this->db->getDbalDatabasePlatform();
	}

	/**
	 * Get table prefix
	 *
	 * @return string
	 */
	protected function getTablePrefix(): string
	{
		return $this->db->getTablePrefix();
	}
}