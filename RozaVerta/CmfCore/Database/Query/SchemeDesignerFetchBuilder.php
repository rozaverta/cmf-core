<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.06.2019
 * Time: 12:36
 */

namespace RozaVerta\CmfCore\Database\Query;

use Closure;
use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Helper\Arr;

/**
 * Class SchemeDesignerFetchBuilder
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
class SchemeDesignerFetchBuilder extends AbstractBuilder
{
	/**
	 * @var ReflectionClass
	 */
	protected $designer;

	/**
	 * Create a new query builder instance.
	 *
	 * @param Connection $connection
	 * @param string     $tableClassName
	 *
	 * @throws \Throwable
	 */
	public function __construct( Connection $connection, string $tableClassName )
	{
		parent::__construct( $connection );

		try
		{
			$designer = new ReflectionClass( $tableClassName );
		} catch( ReflectionException $e )
		{
			throw new NotFoundException( "Invalid scheme designer class {$tableClassName} not found" );
		}

		if( !$designer->isSubclassOf( SchemeDesigner::class ) )
		{
			throw new InvalidArgumentException( "Invalid scheme designer class " . $designer->getName() );
		}

		$tableName = $designer->getMethod( "getTableName" )->invoke( null );
		$schemaBuilder = $designer->getMethod( "getSchemaBuilder" )->invoke( null );
		$this->designer = $designer;
		$this->loadSchemaBuilder( $tableName, $schemaBuilder["alias"] ?? null, $schemaBuilder );
	}

	/**
	 * Support error
	 *
	 * @throws InvalidArgumentException
	 */
	public function select( $select = [ '*' ] )
	{
		throw new InvalidArgumentException( __CLASS__ . " class does't support select method" );
	}

	protected function loadSchemaBuilder( string $table, ? string $alias, array $schemaBuilder )
	{
		// add columns and select
		if( !empty( $schemaBuilder['columns'] ) )
		{
			$this->columns = Arr::wrap( $schemaBuilder['columns'] );
		}

		$this->addTable( $table, $alias, (array) $schemaBuilder["rename"] ?? [] );

		if( isset( $schemaBuilder["select"] ) )
		{
			$this->select = Arr::wrap( $schemaBuilder["select"] );
		}
		else
		{
			// It is not recommended to do so.
			$this->select = $this->tableSchema->getColumnNames();
		}

		// use distinct
		if( isset( $schemaBuilder['distinct'] ) && $schemaBuilder['distinct'] === true )
		{
			$this->setDistinct( true );
		}

		// add tables (not join)
		if( !empty( $schemaBuilder['tables'] ) )
		{
			foreach( (array) $schemaBuilder['tables'] as $tableName => $alias )
			{
				// Without alias, it is not recommended
				if( is_numeric( $tableName ) )
				{
					$tableName = $alias;
					$alias = null;
				}

				$this
					->builder
					->from(
						$tableName, $alias
					);
			}
		}

		// add joins
		if( !empty( $schemaBuilder['joins'] ) )
		{
			foreach( (array) $schemaBuilder["joins"] as $join )
			{
				if( empty( $join["criteria"] ) || empty( $join["tableName"] ) )
				{
					continue; // todo throw error
				}

				$condition = $join["criteria"];
				$criteria = $this->newCriteria();
				$params = $criteria->getParameters();

				if( !$condition instanceof Closure )
				{
					$condition( $criteria );
				}
				else if( is_array( $condition ) )
				{
					$criteria->each( $condition );
				}
				else
				{
					$criteria->raw( $condition );
				}

				$this
					->plainBuilder
					->addJoin(
						$join["tableName"],
						$join["alias"] ?? null,
						$criteria->getSql(),
						$params->getParameters(),
						$params->getTypes(),
						$join["mode"] ?? null
					);
			}
		}

		// add criteria
		if( !empty( $schemaBuilder['where'] ) )
		{
			$this->where( $schemaBuilder['where'] );
		}

		if( !empty( $schemaBuilder['having'] ) )
		{
			$this->having( $schemaBuilder['having'] );
		}

		// add order
		if( !empty( $schemaBuilder['orderBy'] ) )
		{
			$orderBy = Arr::wrap( $schemaBuilder['orderBy'] );

			foreach( $orderBy as $sort => $sortDir )
			{
				if( is_int( $sort ) )
				{
					$sort = $sortDir;
					$sortDir = "ASC";
				}
				else
				{
					$sortDir = strtoupper( $sortDir );
				}

				if( $sortDir !== "DESC" )
				{
					$sortDir = "ASC";
				}

				$this->orderBy( $sort, $sortDir );
			}
		}

		// add group
		if( !empty( $schemaBuilder['groupBy'] ) )
		{
			$this->groupBy( $schemaBuilder['groupBy'] );
		}

		// closure callback
		if( !empty( $schemaBuilder["prepend"] ) && $schemaBuilder["prepend"] instanceof Closure )
		{
			$schemaBuilder["prepend"]( $this );
		}
	}

	protected function prepareResult( array $row )
	{
		return $this->designer->newInstance( $row, $this->connection );
	}
}