<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.06.2019
 * Time: 16:20
 */

namespace RozaVerta\CmfCore\Database\Query;

use Closure;
use Doctrine\DBAL\DBALException;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Interfaces\JoinBuilderInterface;
use RozaVerta\CmfCore\Database\Interfaces\WriteBuilderInterface;
use RozaVerta\CmfCore\Helper\Arr;

/**
 * Class Builder
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
class Builder extends AbstractBuilder implements JoinBuilderInterface, WriteBuilderInterface
{
	/**
	 * @var array
	 */
	protected $cloneable = [ 'where', 'having', 'state' ];

	/**
	 * @var WriteableState | null
	 */
	protected $state;

	protected $resultClassName = null;

	/**
	 * Builder constructor.
	 *
	 * @param Connection  $connection
	 * @param             $table
	 * @param string|null $alias
	 *
	 * @throws \Throwable
	 */
	public function __construct( Connection $connection, string $table, ? string $alias = null )
	{
		parent::__construct( $connection );
		$this->addTable( $table, $alias, [] );
	}

	/**
	 * @param string $className
	 *
	 * @return $this
	 */
	public function setResultClassName( string $className )
	{
		$this->resultClassName = $className;
		return $this;
	}

	/**
	 * Adds an item that is to be returned in the query result.
	 *
	 * @param array|mixed $columns
	 *
	 * @return $this
	 */
	public function select( $columns = [ '*' ] )
	{
		if( $this->select === null )
		{
			$this->select = [];
		}

		foreach( Arr::wrap( $columns ) as $key => $column )
		{
			if( !is_int( $key ) )
			{
				$this->select[] = "{$key} AS {$column}";
			}
			else
			{
				$this->select[] = $column;
			}
		}

		return $this;
	}

	/**
	 * Add table
	 *
	 * @param string      $table
	 * @param string|null $alias
	 * @param array       $rename
	 *
	 * @return $this
	 */
	public function from( $table, ? string $alias = null, array $rename = [] )
	{
		return $this->addTable( $table, $alias, $rename );
	}

	/**
	 * Add a join clause to the query.
	 *
	 * @param string  $mode
	 * @param string  $table
	 * @param string  $alias
	 * @param Closure $condition
	 * @param array   $rename
	 *
	 * @return $this
	 */
	public function join( string $mode, string $table, string $alias, Closure $condition, array $rename = [] )
	{
		return $this->addJoin( $mode, $table, $alias, $condition, $rename );
	}

	/**
	 * Add a inner join clause to the query.
	 *
	 * @param string  $table
	 * @param string  $alias
	 * @param Closure $condition
	 * @param array   $rename
	 *
	 * @return $this
	 */
	public function innerJoin( string $table, string $alias, Closure $condition, array $rename = [] )
	{
		return $this->addJoin( self::JOIN_INNER, $table, $alias, $condition, $rename );
	}

	/**
	 * Add a left join clause to the query.
	 *
	 * @param string  $table
	 * @param string  $alias
	 * @param Closure $condition
	 * @param array   $rename
	 *
	 * @return $this
	 */
	public function leftJoin( string $table, string $alias, Closure $condition, array $rename = [] )
	{
		return $this->addJoin( self::JOIN_LEFT, $table, $alias, $condition, $rename );
	}

	/**
	 * Add a right join clause to the query.
	 *
	 * @param string  $table
	 * @param string  $alias
	 * @param Closure $condition
	 * @param array   $rename
	 *
	 * @return $this
	 */
	public function rightJoin( string $table, string $alias, Closure $condition, array $rename = [] )
	{
		return $this->addJoin( self::JOIN_RIGHT, $table, $alias, $condition, $rename );
	}

	/**
	 * Update a record in the database.
	 *
	 * @param null|array|Closure $data
	 *
	 * @return int
	 *
	 * @throws DBALException
	 */
	public function update( $data = null ): int
	{
		$state = $this->getState( $data );

		return (int) $this
			->calcPlainBuilder()
			->update(
				$state->getParameters(),
				$state->getTypes()
			);
	}

	/**
	 * Insert a new record into the database.
	 *
	 * @param null|array|Closure $data
	 *
	 * @return int
	 *
	 * @throws DBALException
	 */
	public function insert( $data = null ): int
	{
		$state = $this->getState( $data );

		return (int) $this
			->calcPlainBuilder()
			->insert(
				$state->getParameters(),
				$state->getTypes()
			);
	}

	/**
	 * Insert a new record and get the value of the primary key.
	 *
	 * @param null|array|Closure $data
	 *
	 * @return string
	 *
	 * @throws DBALException
	 */
	public function insertGetId( $data = null )
	{
		$state = $this->getState( $data );

		return (int) $this
			->calcPlainBuilder()
			->insertGetId(
				$state->getParameters(),
				$state->getTypes()
			);
	}

	/**
	 * Get the value of the primary key.
	 *
	 * @param null $seqName
	 *
	 * @return string
	 */
	public function lastInsertId($seqName = null)
	{
		return $this->connection->lastInsertId( $seqName );
	}

	/**
	 * Delete a record from the database.
	 *
	 * @return int
	 *
	 * @throws DBALException
	 */
	public function delete(): int
	{
		return (int) $this
			->calcPlainBuilder()
			->delete();
	}

	/**
	 * Get WriteableState object.
	 *
	 * @param null|array|Closure $data
	 *
	 * @return WriteableState
	 */
	public function getState( $data = null ): WriteableState
	{
		if( !isset( $this->state ) )
		{
			$this->state = new WriteableState( $this->tableSchema );
		}

		if( $data )
		{
			if( is_array( $data ) )
			{
				$this->state->values( $data );
			}
			else if( $data instanceof Closure )
			{
				$data( $this->state );
			}
			else
			{
				// todo throw error
			}
		}

		return $this->state;
	}

	protected function prepareResult( array $row )
	{
		if( $this->resultClassName === null )
		{
			return $row;
		}
		else
		{
			$className = $this->resultClassName;
			return new $className( $row, $this->connection );
		}
	}
}