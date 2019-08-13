<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.06.2019
 * Time: 12:36
 */

namespace RozaVerta\CmfCore\Database\Query;

use Closure;
use Doctrine\DBAL\DBALException;
use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Expression;
use RozaVerta\CmfCore\Database\Interfaces\FetchBuilderInterface;
use RozaVerta\CmfCore\Database\Scheme\Table;
use RozaVerta\CmfCore\Support\Collection;
use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;

/**
 * Class AbstractBuilder
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
abstract class AbstractBuilder extends AbstractConnectionContainer implements FetchBuilderInterface
{
	/**
	 * @var PlainBuilder
	 */
	protected $plainBuilder;

	/**
	 * @var Criteria | null
	 */
	protected $where;

	/**
	 * @var Criteria | null
	 */
	protected $having;

	/**
	 * @var string
	 */
	protected $whereType = Criteria::TYPE_AND;

	/**
	 * @var string
	 */
	protected $havingType = Criteria::TYPE_AND;

	/**
	 * @var string
	 */
	protected $table = "";

	/**
	 * @var string|null
	 */
	protected $tableAlias = null;

	/**
	 * @var Table
	 */
	protected $tableSchema;

	/**
	 * @var bool
	 */
	protected $distinct = false;

	/**
	 * @var array
	 */
	protected $columns = [];

	/**
	 * @var array
	 */
	protected $cloneable = [ 'where', 'having' ];

	/**
	 * @var null | array
	 */
	protected $select = null;

	/**
	 * AbstractBuilder constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct( Connection $connection )
	{
		parent::__construct( $connection );
		$this->plainBuilder = new PlainBuilder( $connection );
	}

	/**
	 * Get base table name, without prefix
	 *
	 * @return string
	 */
	public function getTableName(): string
	{
		$table = $this->table;
		if( strpos( $table, "(" ) !== false )
		{
			throw new InvalidArgumentException();
		}

		return $table;
	}

	/**
	 * Get Table schema for base query table
	 *
	 * @return Table|null
	 *
	 * @throws \Throwable
	 */
	public function getTableSchema(): Table
	{
		if( !isset( $this->tableSchema ) )
		{
			$table = $this->table;
			if( strpos( $table, "(" ) !== false || !App::getInstance()->installed() )
			{
				throw new InvalidArgumentException();
			}

			$this->tableSchema = Table::table( $this->table );
		}

		return $this->tableSchema;
	}

	/**
	 * Get full column name
	 *
	 * @param string $name
	 * @return string
	 */
	public function getColumn( string $name ): string
	{
		return $this->columns[$name] ?? $name;
	}

	/**
	 * Set distinct mode.
	 * Force the query to only return distinct results.
	 *
	 * @param bool $value
	 * @return $this
	 */
	public function setDistinct( bool $value = true )
	{
		$this->plainBuilder->setDistinct( $value );
		return $this;
	}

	/**
	 * Add a basic where clause to the query.
	 *
	 * @param string|array|\Closure $name
	 * @param null                  $operator
	 * @param null                  $value
	 * @param null                  $bindName
	 *
	 * @return $this
	 */
	public function where( $name, $operator = null, $value = null, & $bindName = null )
	{
		return $this->criteria( 'where', $name, $operator, $value, $bindName );
	}

	/**
	 * Set where type ("or", "and").
	 *
	 * @param string $type
	 * @return $this
	 */
	public function setWhereType( string $type )
	{
		$this->whereType = $type === Criteria::TYPE_OR ? Criteria::TYPE_OR : Criteria::TYPE_AND;
		return $this;
	}

	/**
	 * Filter unique identify row.
	 *
	 * @param        $value
	 * @param string $column
	 * @param null   $bindName
	 *
	 * @return $this
	 */
	public function whereId( $value, $column = 'id', & $bindName = null )
	{
		return $this
			->limit( 1 )
			->where( $column, Criteria::EQ, (int) $value, $bindName );
	}

	/**
	 * Add a "having" clause to the query.
	 *
	 * @param string|array|\Closure $name
	 * @param null                  $operator
	 * @param null                  $value
	 * @param null                  $bindName
	 *
	 * @return $this
	 */
	public function having( $name, $operator = null, $value = null, & $bindName = null )
	{
		return $this->criteria( 'having', $name, $operator, $value, $bindName );
	}

	/**
	 * Set having type ("or", "and").
	 *
	 * @param string $type
	 * @return $this
	 */
	public function setHavingType( string $type )
	{
		$this->havingType = $type === Criteria::TYPE_OR ? Criteria::TYPE_OR : Criteria::TYPE_AND;
		return $this;
	}

	/**
	 * Set the "limit" value of the query.
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return $this
	 */
	public function limit( int $limit, ? int $offset = null )
	{
		$this
			->plainBuilder
			->limit( max( 1, $limit ), $offset === null ? $offset : max( 0, $offset ) );

		return $this;
	}

	/**
	 * Set the "offset" value of the query.
	 *
	 * @param int $value
	 * @return $this
	 */
	public function offset( int $value )
	{
		$this
			->plainBuilder
			->offset( max( 0, $value ) );

		return $this;
	}

	/**
	 * Set the limit and offset for a given page.
	 *
	 * @param int $page
	 * @param int $perPage
	 *
	 * @return $this
	 */
	public function forPage( int $page, int $perPage = 15 )
	{
		if( $page < 1 )
		{
			$page = 1;
		}
		if( $perPage < 1 )
		{
			$perPage = 1;
		}
		return $this->limit( $perPage, $page > 1 ? ( $page - 1 ) * $perPage : 0 );
	}

	/**
	 * Remove criteria part
	 *
	 * @param $parts
	 *
	 * @return $this
	 */
	public function removePart( $parts )
	{
		if( !is_array( $parts ) )
		{
			$parts = [ $parts ];
		}

		$other = [];

		foreach( $parts as $part )
		{
			if( $part === "where" || $part === "having" )
			{
				unset( $this->{$part} );
				$this->{$part . "Type"} = Criteria::TYPE_AND;
			}
			else if( $part === "select" )
			{
				$this->select = null;
			}
			else
			{
				$other[] = $part;
			}
		}

		if( count( $other ) )
		{
			$this
				->plainBuilder
				->removePart( $parts );
		}

		return $this;
	}

	/**
	 * Adds an ordering to the query results.
	 *
	 * @param string $sort  The ordering expression.
	 * @param string $order The ordering direction.
	 *
	 * @return $this
	 */
	public function orderBy( string $sort, ?string $order = null )
	{
		$sort = $this->getColumn( $sort );

		$this
			->plainBuilder
			->orderBy( $sort, $order );

		return $this;
	}

	/**
	 * Add an "order by random()" clause to the query.
	 *
	 * @param string|null $seed
	 *
	 * @return $this
	 */
	public function orderByRandom( ?string $seed = null )
	{
		$this
			->plainBuilder
			->orderByRandom( $seed );

		return $this;
	}

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param string $column
	 *
	 * @return $this
	 */
	public function latest( string $column = 'created_at' )
	{
		return $this->orderBy( $column, 'DESC' );
	}

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param string $column
	 *
	 * @return $this
	 */
	public function oldest( string $column = 'created_at' )
	{
		return $this->orderBy( $column, 'ASC' );
	}

	/**
	 * Adds a grouping expression to the query.
	 *
	 * @param mixed $groupBy The grouping expression.
	 *
	 * @return $this This DeprecatedBuilder instance.
	 */
	public function groupBy( $groupBy )
	{
		if( is_array( $groupBy ) )
		{
			foreach( $groupBy as & $column )
			{
				$this
					->plainBuilder
					->groupBy(
						$column instanceof Expression ? $column->getValue() : $this->getColumn( (string) $column )
					);
			}
		}
		else
		{
			$this
				->plainBuilder
				->groupBy(
					$groupBy instanceof Expression ? $groupBy->getValue() : $this->getColumn( (string) $groupBy )
				);
		}

		return $this;
	}

	/**
	 * Retrieve the "count" result of the query.
	 *
	 * @param string|null $column
	 *
	 * @return int
	 *
	 * @throws DBALException
	 */
	public function count( ?string $column = null ): int
	{
		return $this->plainBuilder->count( $column, $this->getSelectSql() );
	}

	/**
	 * Retrieve the minimum value of a given column.
	 *
	 * @param string|null $column
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function min( ?string $column = null ): int
	{
		return $this->plainBuilder->min( $column, $this->getSelectSql() );
	}

	/**
	 * Retrieve the maximum value of a given column.
	 *
	 * @param string|null $column
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function max( ?string $column = null ): int
	{
		return $this->plainBuilder->max( $column, $this->getSelectSql() );
	}

	/**
	 * Retrieve the sum of the values of a given column.
	 *
	 * @param string|null $column
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function sum( ?string $column = null ): int
	{
		return $this->plainBuilder->sum( $column, $this->getSelectSql() );
	}

	/**
	 * Retrieve the average of the values of a given column.
	 *
	 * @param string|null $column
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function avg( ?string $column = null ): int
	{
		return $this->plainBuilder->avg( $column, $this->getSelectSql() );
	}

	/**
	 * Deep clone of all expression objects in the SQL parts.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->plainBuilder = clone $this->plainBuilder;

		foreach( $this->cloneable as $name )
		{
			if( isset( $this->{$name} ) )
			{
				$object = clone $this->{$name};
				if( $object instanceof Criteria )
				{
					$object->setParameters( $this->plainBuilder->getParameters() );
				}
				$this->{$name} = $object;
			}
		}
	}

	/**
	 * Get the first value of the first row of the result.
	 *
	 * @param string|null $column
	 *
	 * @return false|mixed
	 *
	 * @throws DBALException
	 */
	public function value( string $column )
	{
		return $this
			->calcPlainBuilder()
			->value( $this->getColumn( $column ) );
	}

	/**
	 * Get the first row of the result.
	 *
	 * @return bool|mixed
	 *
	 * @throws DBALException
	 */
	public function first()
	{
		$row = $this
			->calcPlainBuilder()
			->first( $this->getSelectSql() );

		return $row ? $this->prepareResult( $row ) : false;
	}

	/**
	 * Get collection object as SchemeDesigner items
	 *
	 * @return Collection
	 *
	 * @throws DBALException
	 */
	public function get()
	{
		return new Collection(
			$this
				->calcPlainBuilder()
				->project( function( $row ) {
					return $this->prepareResult( $row );
				}, $this->getSelectSql() )
		);
	}

	/**
	 * Get custom collection result query
	 *
	 * @param Closure     $closure
	 * @param string|null $keyName
	 *
	 * @return Collection
	 *
	 * @throws DBALException
	 */
	public function project( Closure $closure, ?string $keyName = null )
	{
		return new Collection(
			$this
				->calcPlainBuilder()
				->project( function( $row ) use ( $closure ) {
					return $closure( $this->prepareResult( $row ) );
				}, $this->getSelectSql( true ), $keyName )
		);
	}

	protected function addTable( string $table, ? string $alias, array $rename )
	{
		$schema = Table::table( $table );

		if( empty( $this->table ) )
		{
			$this->table = $table;
			$this->tableAlias = $alias;
			$this->tableSchema = $schema;
		}

		$this->columns( $schema, $alias, $rename );
		$this
			->plainBuilder
			->from( $table, $alias );

		return $this;
	}

	protected function addJoin( string $mode, string $table, string $alias, Closure $condition, array $rename )
	{
		$schema = Table::table( $table );
		$this->columns( $schema, $alias, $rename );

		$criteria = $this->newCriteria();
		$params = $criteria->getParameters();
		$condition( $criteria );

		$this
			->plainBuilder
			->addJoin( $table, $alias, $criteria->getSql(), $params->getParameters(), $params->getTypes(), $mode );

		return $this;
	}

	private function columns( Table $schema, ? string $alias, array $rename )
	{
		if( $alias !== null )
		{
			$prefix = $alias . ".";
			foreach( $schema->getColumnNames() as $name )
			{
				$prefixName = $prefix . $name;
				if( isset( $rename[$name] ) )
				{
					if( !isset( $this->columns[$name] ) ) $this->columns[$name] = $rename[$name];
					if( !isset( $this->columns[$prefixName] ) ) $this->columns[$prefixName] = $rename[$name];
				}
				else if( !isset( $this->columns[$name] ) )
				{
					$this->columns[$name] = $prefixName;
				}
			}
		}
		else if( count( $rename ) )
		{
			foreach( $rename as $name => $asName )
			{
				if( !isset( $this->columns[$name] ) ) $this->columns[$name] = $asName;
			}
		}
	}

	/**
	 * Add where or having part
	 *
	 * @param string $type
	 * @param        $name
	 * @param        $operator
	 * @param        $value
	 * @param null   $bindName
	 *
	 * @return $this
	 */
	protected function criteria( string $type, $name, $operator, $value, & $bindName = null )
	{
		if( !isset( $this->{$type} ) )
		{
			$this->{$type} = $this->newCriteria( $this->{$type . "Type"} );
		}

		/** @var Criteria $criteria */
		$criteria = $this->{$type};

		if( $name instanceof Closure )
		{
			$name( $criteria );
		}
		else if( is_array( $name ) )
		{
			$criteria->each( $name );
		}
		else
		{
			if( is_null( $value ) )
			{
				$value = $operator;
				$operator = Criteria::EQ;
			}

			$criteria->add( $name, $operator, $value, $bindName );
		}

		return $this;
	}

	abstract protected function prepareResult( array $row );

	protected function newCriteria( string $type = Criteria::TYPE_AND ): Criteria
	{
		$criteria = new Criteria( $this->connection, new Parameters(), $type );
		$criteria->registerRenameClosure( function( string $name ) {
			return $this->getColumn( $name );
		} );
		return $criteria;
	}

	protected function calcPlainBuilder(): PlainBuilder
	{
		$builder = $this->plainBuilder;
		$builder->removePart( [ "where", "having" ] );

		if( isset( $this->where ) )
		{
			$params = $this->where->getParameters();
			$builder->whereExpr( $this->where->getSql(), $params->getParameters(), $params->getTypes() );
		}

		if( isset( $this->having ) )
		{
			$params = $this->having->getParameters();
			$builder->havingExpr( $this->having->getSql(), $params->getParameters(), $params->getTypes() );
		}

		return $builder;
	}

	protected function columnAlias( string $column, & $columns )
	{
		if( isset( $this->columns[$column] ) && strpos( $this->columns[$column], "." ) === false )
		{
			$columns[] = $column . " AS " . $this->columns[$column];
			return true;
		}
		else
		{
			return false;
		}
	}

	protected function getSelectSql( bool $full = false )
	{
		$tableAlias = $this->tableAlias !== null;

		if( is_array( $this->select ) && count( $this->select ) > 0 )
		{
			$columns = [];

			foreach( $this->select as $column )
			{
				if( $column instanceof Expression )
				{
					$columns[] = $column;
					continue;
				}

				$column = trim( $column );

				if( $this->columnAlias( $column, $columns ) ||
					$tableAlias && strpos( $column, "." ) === false && $this->columnAlias( $this->tableAlias . "." . $column, $columns ) )
				{
					continue;
				}

				if( $tableAlias && $column === "*" )
				{
					$column = $this->tableAlias . "." . $column;
				}

				$columns[] = $column;
			}

			return $columns;
		}
		else
		{
			return $full ? [ $tableAlias ? ( $this->tableAlias . ".*" ) : "*" ] : null;
		}
	}
}