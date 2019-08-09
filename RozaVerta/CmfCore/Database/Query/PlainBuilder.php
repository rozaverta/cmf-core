<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.06.2019
 * Time: 18:37
 */

namespace RozaVerta\CmfCore\Database\Query;

use Doctrine\DBAL\DBALException;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\DatabaseException;
use RozaVerta\CmfCore\Database\Expression;
use RozaVerta\CmfCore\Helper\Str;

/**
 * Class PlainBuilder
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
class PlainBuilder extends AbstractConnectionContainer
{
	/**
	 * @var Parameters
	 */
	protected $parameters;

	/**
	 * @var array
	 */
	protected $sqlParts = [];

	/**
	 * @var null | int
	 */
	protected $limit = null;

	/**
	 * @var null | int
	 */
	protected $offset = null;

	/**
	 * @var bool
	 */
	protected $distinct = false;

	/**
	 * @var array
	 */
	private static $otherParts = [
		"limit" => null,
		"offset" => null,
		"distinct" => false,
	];

	/**
	 * PlainBuilder constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct( Connection $connection )
	{
		parent::__construct( $connection );
		$this->parameters = new Parameters();
	}

	/**
	 * Get parameter setting object
	 *
	 * @return Parameters
	 */
	public function getParameters(): Parameters
	{
		return $this->parameters;
	}

	/**
	 * Add a basic where clause to the query.
	 *
	 * @param string $expression
	 * @param array  $values
	 * @param array  $types
	 *
	 * @return PlainBuilder
	 */
	public function whereExpr( string $expression, array $values = [], array $types = [] )
	{
		return $this->addPart( 'where', ' AND ', $expression, $values, $types );
	}

	/**
	 * Add an "OR WHERE" clause to the query.
	 *
	 * @param string $expression
	 * @param array  $values
	 * @param array  $types
	 *
	 * @return PlainBuilder
	 */
	public function orWhereExpr( string $expression, array $values = [], array $types = [] )
	{
		return $this->addPart( 'where', ' OR ', $expression, $values, $types );
	}

	/**
	 * Add a basic where comparison to the query.
	 *
	 * @param string $name
	 * @param        $operator
	 * @param null   $value
	 *
	 * @return PlainBuilder
	 */
	public function where( string $name, $operator, $value = null )
	{
		return $this->comparison( 'where', ' AND ', $name, $operator, $value );
	}

	/**
	 * Add an "OR WHERE" comparison to the query.
	 *
	 * @param string $name
	 * @param        $operator
	 * @param null   $value
	 *
	 * @return PlainBuilder
	 */
	public function orWhere( string $name, $operator, $value = null )
	{
		return $this->comparison( 'where', ' OR ', $name, $operator, $value );
	}

	/**
	 * Add a "HAVING" clause to the query.
	 *
	 * @param string $expression
	 * @param array  $values
	 * @param array  $types
	 *
	 * @return PlainBuilder
	 */
	public function havingExpr( string $expression, array $values = [], array $types = [] )
	{
		return $this->addPart( 'having', ' AND ', $expression, $values, $types );
	}

	/**
	 * Add a "OR HAVING" clause to the query.
	 *
	 * @param string $expression
	 * @param array  $values
	 * @param array  $types
	 *
	 * @return PlainBuilder
	 */
	public function orHavingExpr( string $expression, array $values = [], array $types = [] )
	{
		return $this->addPart( 'having', ' OR ', $expression, $values, $types );
	}

	/**
	 * Add a "HAVING" comparison to the query.
	 *
	 * @param string $name
	 * @param        $operator
	 * @param null   $value
	 *
	 * @return PlainBuilder
	 */
	public function having( string $name, $operator, $value = null )
	{
		return $this->comparison( 'having', ' AND ', $name, $operator, $value );
	}

	/**
	 * Add a "OR HAVING" comparison to the query.
	 *
	 * @param string $name
	 * @param        $operator
	 * @param null   $value
	 *
	 * @return PlainBuilder
	 */
	public function orHaving( string $name, $operator, $value = null )
	{
		return $this->comparison( 'having', ' OR ', $name, $operator, $value );
	}

	/**
	 * Add an "order by" clause to the query.
	 *
	 * @param string $sort
	 * @param string $order
	 * @return PlainBuilder
	 */
	public function orderBy( string $sort, ?string $order = null )
	{
		$expression = $this->grammar->wrap( $sort ) . ( $order && strtoupper( $order ) === "DESC" ? " DESC" : " ASC" );
		return $this->addPart( 'orderBy', ', ', $expression );
	}

	/**
	 * Add an "order by random()" clause to the query.
	 *
	 * @param string|null $seed
	 *
	 * @return PlainBuilder
	 */
	public function orderByRandom( ?string $seed = null )
	{
		return $this->addPart( 'orderBy', ', ', $this->grammar->orderByRandom( $seed ) );
	}

	/**
	 * Add an "order by" expression to the query.
	 *
	 * @param string $expression
	 *
	 * @return PlainBuilder
	 */
	public function orderByExpr( string $expression )
	{
		return $this->addPart( 'orderBy', ', ', $expression );
	}

	/**
	 * Add a "group by" clause to the query.
	 *
	 * @param string $column
	 *
	 * @return PlainBuilder
	 */
	public function groupBy( string $column )
	{
		return $this->addPart( 'groupBy', ', ', $this->grammar->wrap( $column ) );
	}

	/**
	 * Add a "group by" expression to the query.
	 *
	 * @param string $expression
	 *
	 * @return PlainBuilder
	 */
	public function groupByExpr( string $expression )
	{
		return $this->addPart( 'groupBy', ', ', $expression );
	}

	/**
	 * Set the "limit" value of the query.
	 *
	 * @param int|null $limit
	 * @param int|null $offset
	 * @return $this
	 */
	public function limit( ? int $limit, ? int $offset = null )
	{
		$this->limit = $limit;
		if( !is_null( $offset ) )
		{
			$this->offset = $offset;
		}
		return $this;
	}

	/**
	 * Set the "offset" value of the query.
	 *
	 * @param int|null $offset
	 *
	 * @return $this
	 */
	public function offset( ? int $offset )
	{
		$this->offset = $offset;
		return $this;
	}

	/**
	 * Set or remove the "distinct" mode of the select query.
	 *
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function setDistinct( bool $value )
	{
		$this->distinct = $value;
		return $this;
	}

	/**
	 * Add table or tables part
	 *
	 * @param string      $table
	 * @param null|string $alias
	 *
	 * @return PlainBuilder
	 */
	public function from( $table, ?string $alias = null )
	{
		return $this->addPart( 'from', ', ', $this->grammar->wrapTable( $table, $alias ) );
	}

	/**
	 * Add join request
	 *
	 * @param string            $table
	 * @param                   $alias
	 * @param string|Expression $expression
	 * @param array             $values
	 * @param array             $types
	 * @param null|string       $mode
	 *
	 * @return $this
	 *
	 * @throws DatabaseException
	 */
	public function addJoin( $table, ? string $alias, $expression, array $values = [], array $types = [], ? string $mode = null )
	{
		if( empty( $this->sqlParts['from'] ) )
		{
			throw DatabaseException::unknownSourceTable();
		}

		$table = $this->grammar->wrapTable( $table, $alias );
		if( empty( $mode ) )
		{
			$mode = "INNER JOIN";
		}
		else if( stripos( $mode, "JOIN" ) === false )
		{
			$mode .= " JOIN";
		}

		return $this->addPart( "from", " ", " {$mode} {$table} ON {$expression}", $values, $types );
	}

	/**
	 * Get the first result of the query as an associative array.
	 *
	 * @param null|string|array $select
	 *
	 * @return false|array[]
	 *
	 * @throws DBALException
	 */
	public function first( $select = null )
	{
		$this->limit = 1;
		return $this
			->connection
			->fetchAssoc(
				$this->createSqlForSelect( $select ), $this->parameters->getParameters(), $this->parameters->getTypes()
			);
	}

	/**
	 * Get a single column's value from the first result of a query.
	 *
	 * @param string $column
	 *
	 * @return false|mixed
	 *
	 * @throws DBALException
	 */
	public function value( string $column )
	{
		$this->limit = 1;
		return $this
			->connection
			->fetchColumn(
				$this->createSqlForSelect( $this->grammar->wrapSafe( $column ) ), $this->parameters->getParameters(), 0, $this->parameters->getTypes()
			);
	}

	/**
	 * Execute the query as a "select" statement and get all result as an array with elements of an associative array.
	 *
	 * @param null|string|array $select
	 *
	 * @return array[]
	 *
	 * @throws DBALException
	 */
	public function get( $select = null ): array
	{
		return $this->connection->fetchAll(
			$this->createSqlForSelect( $select ), $this->parameters->getParameters(), $this->parameters->getTypes()
		);
	}

	/**
	 * Get custom collection result query.
	 * Execute the query as a "select" statement and applies the callback to the all result
	 * (elements of the given arrays)
	 *
	 * @param \Closure          $closure
	 * @param null|string|array $select
	 *
	 * @param string|null       $keyName
	 * @return mixed[]
	 *
	 * @throws DBALException
	 */
	public function project( \Closure $closure, $select = null, ?string $keyName = null ): array
	{
		$stmt = $this
			->connection
			->executeQuery(
				$this->createSqlForSelect( $select ),
				$this->parameters->getParameters(),
				$this->parameters->getTypes()
			);

		$useKey = $keyName !== null && strlen( $keyName ) > 0;
		$rows = [];

		while( $row = $stmt->fetch() )
		{
			if( $useKey )
			{
				$rows[$row[$keyName]] = $closure( $row );
			}
			else
			{
				$rows[] = $closure( $row );
			}
		}

		$stmt->closeCursor();

		return $rows;
	}

	/**
	 * Insert a new record into the database.
	 *
	 * @param array $data
	 * @param array $types
	 *
	 * @return int
	 *
	 * @throws DBALException
	 */
	public function insert( array $data, array $types = [] ): int
	{
		return $this->connection->executeWritable(
			$this->getSqlForInsert( $data, $types ), $this->parameters->getParameters(), $this->parameters->getTypes()
		);
	}

	/**
	 * Insert a new record and get the value of the primary key.
	 *
	 * @param array $data
	 * @param array $types
	 *
	 * @return int|string|null
	 *
	 * @throws DBALException
	 */
	public function insertGetId( array $data, array $types = [] )
	{
		return $this->connection->executeInsertGetId(
			$this->getSqlForInsert( $data, $types ), $this->parameters->getParameters(), $this->parameters->getTypes()
		);
	}

	/**
	 * Update a record in the database.
	 *
	 * @param array $data
	 * @param array $types
	 *
	 * @return int
	 *
	 * @throws DBALException
	 */
	public function update( array $data, array $types = [] ): int
	{
		return $this->connection->executeWritable(
			$this->getSqlForUpdate( $data, $types ), $this->parameters->getParameters(), $this->parameters->getTypes()
		);
	}

	/**
	 * Check rows exists in current query
	 *
	 * @param string|null $select
	 *
	 * @return bool
	 *
	 * @throws DBALException
	 */
	public function exists( $select = null ): bool
	{
		/** @var Parameters $params */
		$row = $this
			->connection
			->fetchAssoc(
				$this->grammar->compileExists( $this, $select, $params ), $params->getParameters(), $params->getTypes()
			);

		$value = $row["exists"] ?? false;
		return is_bool( $value ) ? $value : $value > 0;
	}

	/**
	 * Retrieve the "count" result of the query.
	 *
	 * @param string|null $column
	 * @param string|null $select
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function count( ?string $column = null, ?string $select = null ): int
	{
		return $this->aggregate( __FUNCTION__, $column, $select );
	}

	/**
	 * Retrieve the minimum value of a given column.
	 *
	 * @param string|null $column
	 * @param string|null $select
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function min( ?string $column = null, ?string $select = null ): int
	{
		return $this->aggregate( __FUNCTION__, $column, $select );
	}

	/**
	 * Retrieve the maximum value of a given column.
	 *
	 * @param string|null $column
	 * @param string|null $select
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function max( ?string $column = null, ?string $select = null ): int
	{
		return $this->aggregate( __FUNCTION__, $column, $select );
	}

	/**
	 * Retrieve the sum of the values of a given column.
	 *
	 * @param string|null $column
	 * @param string|null $select
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function sum( ?string $column = null, ?string $select = null ): int
	{
		return $this->aggregate( __FUNCTION__, $column, $select );
	}

	/**
	 * Retrieve the average of the values of a given column.
	 *
	 * @param string|null $column
	 * @param string|null $select
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function avg( ?string $column = null, ?string $select = null ): int
	{
		return $this->aggregate( __FUNCTION__, $column, $select );
	}

	/**
	 * Execute an aggregate function on the database.
	 *
	 * @param string $function
	 * @param        $column
	 * @param        $select
	 *
	 * @return int
	 *
	 * @throws DBALException
	 */
	private function aggregate( string $function, $column, $select ): int
	{
		if( empty( $column ) )
		{
			$column = "*";
		}

		$function = strtoupper( $function );

		if( $this->hasPart( "groupBy" ) || $this->hasPart( "having" ) )
		{
			$col = end( explode( ".", $column ) );
			$funcColumn = "*";

			if( $col !== "*" )
			{
				$column = $this->grammar->wrapAs( $column, "uid" );
				$funcColumn = "uid";
			}
			else
			{
				$column = $this->grammar->wrap( $column );
			}

			if( !empty( $select ) )
			{
				if( is_array( $select ) )
				{
					$column .= ", " . $this->grammar->columnize( $select );
				}
				else
				{
					$column .= ", {$select}";
				}
			}

			$sql = "SELECT {$function}("
				. $this->grammar->wrap( "table_count.{$funcColumn}" )
				. ") FROM ("
				. $this->createSqlForSelect( $column )
				. ") AS "
				. $this->grammar->wrap( "table_count" );
		}
		else
		{
			$sql = $this->createSqlForSelect( $function . '(' . $this->grammar->wrap( $column ) . ')' );
		}

		return (int) $this
			->connection
			->fetchColumn(
				$sql, $this->parameters->getParameters(), 0, $this->parameters->getTypes()
			);
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
		return $this->connection->executeWritable(
			$this->getSqlForDelete(), $this->parameters->getParameters(), $this->parameters->getTypes()
		);
	}

	/**
	 * Get SQL part (query and parameter names)
	 *
	 * @param string $name
	 *
	 * @return array
	 */
	public function getPart( string $name ): ?array
	{
		return $this->sqlParts[$name] ?? null;
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

		foreach( $parts as $part )
		{
			if( isset( $this->sqlParts[$part] ) )
			{
				foreach( $this->sqlParts[$part]["params"] as $name )
				{
					$this->parameters->delete( $name );
				}
				unset( $this->sqlParts[$part] );
			}
			else if( array_key_exists( $part, self::$otherParts ) )
			{
				$this->{$part} = self::$otherParts[$part];
			}
		}

		return $this;
	}

	/**
	 * Query part exists
	 *
	 * @param string $part
	 *
	 * @return bool
	 */
	public function hasPart( string $part ): bool
	{
		if( isset( $this->sqlParts[$part] ) )
		{
			$value = $this->{$part} ?? false;
			return $value !== null && $value !== false;
		}
		else
		{
			return isset( $this->sqlParts[$part] );
		}
	}

	/**
	 * Converts this instance into an SELECT string in SQL.
	 *
	 * @param mixed $select
	 *
	 * @return string
	 *
	 * @throws DBALException
	 */
	public function getSqlForSelect( $select = null ): string
	{
		return $this->createSqlForSelect( $select );
	}

	/**
	 * Converts this instance into an SELECT string in SQL.
	 *
	 * @param array|string|null $select
	 * @param bool              $forCalc
	 *
	 * @return string
	 *
	 * @throws DBALException
	 */
	private function createSqlForSelect( $select, bool $forCalc = false ): string
	{
		$this->clearParts( 'from', 'where', 'groupBy', 'having', $forCalc ? '' : 'orderBy' );

		$query = $this->distinct ? "SELECT DISTINCT " : "SELECT ";

		if( empty( $select ) )
		{
			$query .= "*";
		}
		else if( is_array( $select ) )
		{
			$query .= $this->grammar->columnize( $select );
		}
		else if( $select instanceof Expression )
		{
			$query .= $select->getValue();
		}
		else
		{
			$query .= (string) $select;
		}

		$query .= " FROM " . $this->getFromPart();
		$query .= $this->getQueryPart( 'where', ' WHERE ' );
		$query .= $this->getQueryPart( 'groupBy', ' GROUP BY ' );
		$query .= $this->getQueryPart( 'having', ' HAVING ' );

		if( $forCalc )
		{
			return $query;
		}

		$query .= $this->getQueryPart( 'orderBy', ' ORDER BY ' );

		return $this->modifyLimitPart( $query );
	}

	/**
	 * Converts this instance into an INSERT string in SQL.
	 *
	 * @param array $data
	 * @param array $types
	 *
	 * @return string
	 *
	 * @throws DBALException
	 */
	private function getSqlForInsert( array $data, array $types = [] ): string
	{
		$this->clearParts( 'from' );
		$this->dataNull( $data, $types );

		$query = "INSERT INTO " . $this->getFromPart( true );
		$columns = [];
		$values = [];
		$params = [];

		foreach( $data as $name => $value )
		{
			$columns[] = $this->grammar->wrap( $name );

			if( $value instanceof Expression )
			{
				$values[] = $value->getValue();
			}
			else
			{
				$param = $this->parameters->bindForColumn( $name, $value, $types[$name] ?? Parameters::inferType( $value ) );
				$values[] = $param;
				$params[] = $param;
			}
		}

		$query .= ' (' . implode( ', ', $columns ) . ')';
		$query .= ' VALUES(' . implode( ', ', $values ) . ')';

		$this->sqlParts["insert"] = compact( 'query', 'params' );

		return $query;
	}

	/**
	 * Converts this instance into an UPDATE string in SQL.
	 *
	 * @param array $data
	 * @param array $types
	 *
	 * @return string
	 *
	 * @throws DBALException
	 */
	public function getSqlForUpdate( array $data, array $types = [] ): string
	{
		$this->clearParts( 'from', 'where', 'orderBy' );
		$this->dataNull( $data, $types );

		$query = "UPDATE " . $this->getFromPart( true ) . " SET ";
		$parts = [];
		$params = [];

		foreach( $data as $name => $value )
		{
			if( is_int( $name ) )
			{
				$parts[] = (string) $value;
			}
			else if( $value instanceof Expression )
			{
				$parts[] = $this->grammar->wrap( $name ) . " = " . $value->getValue();
			}
			else
			{
				$param = $this->parameters->bindForColumn( $name, $value, $types[$name] ?? Parameters::inferType( $value ) );
				$parts[] = $this->grammar->wrap( $name ) . " = " . $param;
				$params[] = $param;
			}
		}

		$query .= implode( ", ", $parts );
		$query .= $this->getQueryPart( 'where', ' WHERE ' );
		$query .= $this->getQueryPart( 'orderBy', ' ORDER BY ' );
		$query = $this->modifyLimitPart( $query );

		$this->sqlParts["update"] = compact( 'query', 'params' );

		return $query;
	}

	/**
	 * Converts this instance into a DELETE string in SQL.
	 *
	 * @return string
	 *
	 * @throws DBALException
	 */
	public function getSqlForDelete(): string
	{
		$this->clearParts( 'from', 'where', 'orderBy' );

		$query = "DELETE FROM " . $this->getFromPart( true );
		$query .= $this->getQueryPart( 'where', ' WHERE ' );
		$query .= $this->getQueryPart( 'orderBy', ' ORDER BY ' );
		$query = $this->modifyLimitPart( $query );

		$this->sqlParts["delete"] = [ 'query' => $query, 'params' => [] ];

		return $query;
	}

	public function __clone()
	{
		$this->parameters = clone $this->parameters;
	}

	/**
	 * Get from part
	 *
	 * @param bool $writable
	 *
	 * @return string
	 *
	 * @throws DatabaseException
	 */
	private function getFromPart( bool $writable = false ): string
	{
		if( !isset( $this->sqlParts['from'] ) )
		{
			throw DatabaseException::unknownSourceTable();
		}

		$part = &$this->sqlParts['from'];
		$query = $part['query'];
		if( $writable )
		{
			if( count( $part['params'] ) || strpos( $query, '(' ) !== false )
			{
				throw DatabaseException::invalidQueryParameter( "Writable query cannot use functions or parameters for table name" );
			}
		}

		return $query;
	}

	/**
	 * Set null values for insert or update data object
	 *
	 * @param array $data
	 * @param array $types
	 */
	private function dataNull( array & $data, array $types ): void
	{
		if( !count( $data ) )
		{
			foreach( array_keys( $types ) as $key )
			{
				$data[$key] = null;
			}
		}
	}

	/**
	 * Get limit and offset part
	 *
	 * @param string $query
	 * @return string
	 *
	 * @throws DBALException
	 */
	private function modifyLimitPart( string $query ): string
	{
		if( $this->limit !== null || $this->offset !== null )
		{
			return $this
				->grammar
				->compileLimitQuery(
					$query,
					$this->limit,
					$this->offset
				);
		}
		else
		{
			return $query;
		}
	}

	/**
	 * Get the compiled part
	 *
	 * @param string $name
	 * @param string $prefix
	 *
	 * @return string
	 */
	private function getQueryPart( string $name, string $prefix ): string
	{
		if( isset( $this->sqlParts[$name] ) )
		{
			return $prefix . $this->sqlParts[$name]["query"];
		}
		else
		{
			return "";
		}
	}

	/**
	 * Add where or having comparison part
	 *
	 * @param string $type
	 * @param string $delimiter
	 * @param string $name
	 * @param        $operator
	 * @param        $value
	 *
	 * @return PlainBuilder
	 */
	private function comparison( string $type, string $delimiter, string $name, $operator, $value )
	{
		$name = $this->grammar->wrap( $name );
		if( $value === null )
		{
			$value = $operator;
			$operator = "=";
		}
		return $this->addPart( $type, $delimiter, "{$name} {$operator} ?", [ $value ] );
	}

	/**
	 * Add part
	 *
	 * @param string $name
	 * @param string $delimiter
	 * @param string $query
	 * @param array  $values
	 * @param array  $types
	 *
	 * @return $this
	 */
	private function addPart( string $name, string $delimiter, string $query, $values = [], $types = [] )
	{
		if( !isset( $this->sqlParts[$name] ) )
		{
			$this->sqlParts[$name] = [
				'query' => "",
				'params' => [],
			];
		}
		else
		{
			$this->sqlParts[$name]['query'] .= $delimiter;
		}

		$part = &$this->sqlParts[$name];
		$params = [];
		$autoConverter = false;

		foreach( $values as $key => $value )
		{
			$type = $types[$key] ?? Parameters::inferType( $value );
			if( is_string( $key ) && substr( $key, 0, 1 ) === ":" )
			{
				$part['params'][] = $this->parameters->bind( $key, $value, $type );
			}
			else
			{
				$param = $this->parameters->bindNext( $value, $type );
				$params[] = $param;
				$part['params'][] = $param;
				$autoConverter = true;
			}
		}

		$part["query"] .= $autoConverter ? Str::replaceEscapeArray( "?", "?", $params, $query ) : $query;

		return $this;
	}

	/**
	 * Clear unused parts of SQL
	 *
	 * @param array $saved
	 */
	private function clearParts( ...$saved )
	{
		$parts = array_keys( $this->sqlParts );
		foreach( $parts as $part )
		{
			if( !in_array( $part, $saved, true ) )
			{
				$this->removePart( $part );
			}
		}
	}
}