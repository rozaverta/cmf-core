<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 20:38
 */

namespace RozaVerta\CmfCore\Database\Scheme;

use InvalidArgumentException;
use RozaVerta\CmfCore\Database\DatabaseManager as DB;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Module\Module;
use RozaVerta\CmfCore\Module\ResourceJson;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Support\Prop;

class TableLoader
{
	use ExtraTrait;
	use ModuleGetterTrait;

	/**
	 * @var int
	 */
	protected $moduleId;

	/**
	 * @var string
	 */
	protected $name;

	protected $primaryKeyName = null;

	protected $columns = [];

	protected $indexes = [];

	protected $fkConstraint = [];

	protected $options = [];

	/**
	 * @var ResourceJson
	 */
	protected $resource;

	/**
	 * TableLoader constructor.
	 *
	 * @param string $name
	 * @param ModuleInterface|null $module
	 * @param string|null $cacheVersion
	 *
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function __construct( string $name, ?ModuleInterface $module = null, ?string $cacheVersion = null )
	{
		// find module in database scheme table
		if( !$module )
		{
			$moduleId = DB::table("scheme_tables")
				->where("name", $name)
				->select(["module_id"])
				->value();

			if( ! is_numeric($moduleId) )
			{
				throw new NotFoundException("Table '{$name}' not found");
			}

			$module = Module::module((int) $moduleId);
		}

		$resource = $module->getResourceJson("db_" . $name, $cacheVersion );
		if( $resource->getType() !== "#/database_table" )
		{
			throw new InvalidArgumentException("Invalid resource file type for the '{$name}' table");
		}

		$this->name = $name;
		$this->setModule($module);

		foreach($resource->getArray("columns") as $row)
		{
			$this->_addColumn( is_array($row) ? $row : ["name" => $row] );
		}

		foreach($resource->getArray("indexes") as $row)
		{
			$this->_addIndex( Arr::wrap($row) );
		}

		$primaryKey = $resource->get("primaryKey");
		if( $primaryKey )
		{
			$this->_addIndex([
				"type" => "primary",
				"columns" => Arr::wrap($primaryKey)
			]);
		}

		foreach($resource->getArray("foreignKeys") as $row)
		{
			$this->_addForeignKey( (array) $row );
		}

		$this->resource = $resource;
		$this->options = $resource->getArray("options");
		$this->extra = new Prop( $resource->getArray("extra") );
	}

	protected function _addColumn(array $row)
	{
		if( empty($row["name"]) )
		{
			throw $this->createException("Empty column name.");
		}

		$name = $row["name"];
		if( array_key_exists($name, $this->columns) )
		{
			throw $this->createException("The '{$name}' column is already exists.");
		}

		$this->columns[$name] = new Column($name, $row);
	}

	protected function _addIndex(array $row)
	{
		if( empty($row["columns"]) )
		{
			if( count($row) && ! Arr::associative($row) )
			{
				$row = [
					"columns" => $row
				];
			}
			else
			{
				throw $this->createException("Empty index columns.");
			}
		}

		$type = empty($row["type"]) ? "INDEX" : strtoupper($row["type"]);
		$primary = $type === "PRIMARY";
		$columns = Arr::wrap($row["columns"]);

		if( empty($row["name"]) )
		{
			$name = $primary ? "PRIMARY" : Index::createName($this->name, $columns);
		}
		else
		{
			$name = $row["name"];
		}

		if($primary)
		{
			if( ! $this->primaryKeyName )
			{
				$this->primaryKeyName = $name;
			}
			else
			{
				throw $this->createException("Duplicate primary key.");
			}
			if( count($columns) !== 1 )
			{
				throw $this->createException("Primary key must contain only one column.");
			}
		}

		if( array_key_exists($name, $this->indexes) || array_key_exists($name, $this->fkConstraint) )
		{
			throw $this->createException("The '{$name}' index is already exists.");
		}

		$this->indexes[$name] = new Index($name, $columns, $type);
	}

	protected function _addForeignKey(array $row)
	{
		$columns = isset($row["columns"]) ? Arr::wrap($row["columns"]) : null;
		$fkColumns = isset($row["fkColumns"]) ? Arr::wrap($row["fkColumns"]) : null;
		$name = $row["name"] ?? null;
		if( !$name )
		{
			$name = ForeignKeyConstraint::createName($this->name, $columns, "foreign");
		}

		if( empty($columns) )
		{
			throw $this->createException("Empty foreign key columns.");
		}

		if( empty($row["fkTableName"]) )
		{
			throw $this->createException("Empty table name for foreign key constraint.");
		}

		if( empty($fkColumns) )
		{
			throw $this->createException("Empty columns for foreign key constraint table.");
		}

		$options = [];
		foreach(["onUpdate", "onDelete", "default"] as $option)
		{
			if( isset($row[$option]) )
			{
				$options[$option] = $row[$option];
			}
		}

		if( array_key_exists($name, $this->indexes) || array_key_exists($name, $this->fkConstraint) )
		{
			throw $this->createException("The '{$name}' index is already exists.");
		}

		$this->fkConstraint[$name] = new ForeignKeyConstraint($name, $columns, $row["fkTableName"], $fkColumns, $options);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return Column[]
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}

	/**
	 * @return Index[]
	 */
	public function getIndexes(): array
	{
		return $this->indexes;
	}

	/**
	 * @return ForeignKeyConstraint[]
	 */
	public function getForeignKeyConstraints(): array
	{
		return $this->fkConstraint;
	}

	/**
	 * @return array
	 */
	public function getOptions(): array
	{
		return $this->options;
	}

	/**
	 * @return string|null
	 */
	public function getPrimaryKeyName(): ?string
	{
		return $this->primaryKeyName;
	}

	/**
	 * @return ResourceJson
	 */
	public function getResource(): ResourceJson
	{
		return $this->resource;
	}

	protected function createException( $text, $exception = InvalidArgumentException::class )
	{
		$text .= " Database resource config file db_{$this->name}.json";
		return new $exception($text);
	}
}