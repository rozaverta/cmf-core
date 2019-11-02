<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.10.2019
 * Time: 3:19
 */

namespace RozaVerta\CmfCore\Database\Scheme;

use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Support\Prop;

class TableDataLoader
{
	use ExtraTrait;

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
	 * TableDataLoader constructor.
	 *
	 * @param string $name
	 * @param array  $columns
	 * @param array  $indexes
	 * @param array  $primaryKeys
	 * @param array  $foreignKeys
	 * @param array  $options
	 * @param array  $extra
	 */
	public function __construct( string $name, array $columns = [], array $indexes = [], array $primaryKeys = [], array $foreignKeys = [], array $options = [], array $extra = [] )
	{
		$this->name = $name;
		$this->load(
			$columns, $indexes, $primaryKeys, $foreignKeys, $options, $extra
		);
	}

	protected function load( array $columns = [], array $indexes = [], array $primaryKeys = [], array $foreignKeys = [], array $options = [], array $extra = [] )
	{
		foreach( $columns as $row )
		{
			$this->_addColumn( is_array( $row ) ? $row : [ "name" => $row ] );
		}

		foreach( $indexes as $row )
		{
			$this->_addIndex( Arr::wrap( $row ) );
		}

		if( count( $primaryKeys ) )
		{
			$this->_addIndex( [
				"type" => "primary",
				"columns" => $primaryKeys,
			] );
		}

		foreach( $foreignKeys as $row )
		{
			$this->_addForeignKey( (array) $row );
		}

		$this->options = $options;
		$this->extra = new Prop( $extra );
	}

	protected function _addColumn( array $row )
	{
		if( empty( $row["name"] ) )
		{
			throw $this->createException( "Empty column name." );
		}

		$name = $row["name"];
		if( array_key_exists( $name, $this->columns ) )
		{
			throw $this->createException( "The \"{$name}\" column is already exists." );
		}

		$this->columns[$name] = new Column( $name, $row );
	}

	protected function _addIndex( array $row )
	{
		if( empty( $row["columns"] ) )
		{
			if( count( $row ) && !Arr::associative( $row ) )
			{
				$row = [
					"columns" => $row,
				];
			}
			else
			{
				throw $this->createException( "Empty index columns." );
			}
		}

		$type = empty( $row["type"] ) ? "INDEX" : strtoupper( $row["type"] );
		$primary = $type === "PRIMARY";
		$columns = Arr::wrap( $row["columns"] );

		if( empty( $row["name"] ) )
		{
			$name = $primary ? "PRIMARY" : Index::createName( $this->name, $columns );
		}
		else
		{
			$name = $row["name"];
		}

		if( $primary )
		{
			if( !$this->primaryKeyName )
			{
				$this->primaryKeyName = $name;
			}
			else
			{
				throw $this->createException( "Duplicate primary key." );
			}
			if( count( $columns ) !== 1 )
			{
				throw $this->createException( "Primary key must contain only one column." );
			}
		}

		if( array_key_exists( $name, $this->indexes ) || array_key_exists( $name, $this->fkConstraint ) )
		{
			throw $this->createException( "The \"{$name}\" index is already exists." );
		}

		$this->indexes[$name] = new Index( $name, $columns, $type );
	}

	protected function _addForeignKey( array $row )
	{
		$columns = isset( $row["columns"] ) ? Arr::wrap( $row["columns"] ) : null;
		$fkColumns = isset( $row["fkColumns"] ) ? Arr::wrap( $row["fkColumns"] ) : null;
		$name = $row["name"] ?? null;
		if( !$name )
		{
			$name = ForeignKeyConstraint::createName( $this->name, $columns, "foreign" );
		}

		if( empty( $columns ) )
		{
			throw $this->createException( "Empty foreign key columns." );
		}

		if( empty( $row["fkTableName"] ) )
		{
			throw $this->createException( "Empty table name for foreign key constraint." );
		}

		if( empty( $fkColumns ) )
		{
			throw $this->createException( "Empty columns for foreign key constraint table." );
		}

		$options = [];
		foreach( [ "onUpdate", "onDelete", "default" ] as $option )
		{
			if( isset( $row[$option] ) )
			{
				$options[$option] = $row[$option];
			}
		}

		if( array_key_exists( $name, $this->indexes ) || array_key_exists( $name, $this->fkConstraint ) )
		{
			throw $this->createException( "The \"{$name}\" index is already exists." );
		}

		$this->fkConstraint[$name] = new ForeignKeyConstraint( $name, $columns, $row["fkTableName"], $fkColumns, $options );
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

	protected function createException( $text, $exception = InvalidArgumentException::class )
	{
		return new $exception( $text );
	}
}