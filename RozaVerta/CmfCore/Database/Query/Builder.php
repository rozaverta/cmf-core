<?php

namespace RozaVerta\CmfCore\Database\Query;

use Closure;
use Doctrine\DBAL\DBALException;
use ReflectionClass;
use ReflectionException;
use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;
use RozaVerta\CmfCore\Database\Scheme\Table;
use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Helper\Str;
use RozaVerta\CmfCore\Support\Collection;
use InvalidArgumentException;

class Builder
{
	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * @var DbalQueryBuilder
	 */
	protected $builder;

	/**
	 * @var ReflectionClass
	 */
	protected $designer;

	/**
	 * @var CriteriaBuilder | null
	 */
	protected $where;

	/**
	 * @var CriteriaBuilder | null
	 */
	protected $having;

	/**
	 * @var StateUpdateBuilder | null
	 */
	protected $update;

	/**
	 * @var StateInsertBuilder | null
	 */
	protected $insert;

	/**
	 * @var string
	 */
	protected $table = "";

	/**
	 * @var string
	 */
	protected $tableAlias = "";

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
	protected $columnsSelect = [];

	/**
	 * Create a new query builder instance.
	 *
	 * @param $table
	 * @param Connection $connection
	 */
	public function __construct( $table, Connection $connection )
	{
		$this->connection = $connection;
		$this->builder = new DbalQueryBuilder( $connection->getDbalConnection() );

		if( is_array($table) )
		{
			$alias = $table[1] ?? $table["alias"] ?? null;
			$table = $table[0] ?? $table["table"] ?? current($table);
		}
		else
		{
			$alias = null;
		}

		if( $table instanceof Builder )
		{
			$builder = $table->getDbalQueryBuilder();
			$this
				->builder
				->setParameters( $builder->getParameters(), $builder->getParameterTypes() );

			$table = "(" . $builder->getSQL() . ")";
			if( !$alias )
			{
				$alias = "tmp_table";
			}
		}

		$this->table((string) $table, $alias);
	}

	/**
	 * @return Connection
	 */
	public function getConnection()
	{
		return $this->connection;
	}

	/**
	 * @return \Doctrine\DBAL\Connection
	 */
	public function getDbalConnection()
	{
		return $this->connection->getDbalConnection();
	}

	/**
	 * @return DbalQueryBuilder
	 */
	public function getDbalQueryBuilder(): DbalQueryBuilder
	{
		return $this->builder;
	}

	/**
	 * @return Table|null
	 */
	public function getTableSchema(): ?Table
	{
		static $prevent = false;

		if( isset($this->tableSchema) )
		{
			return $this->tableSchema;
		}

		$table = $this->table;
		if( $prevent || strpos($table, "(") !== false || ! App::getInstance()->isInstall() )
		{
			return null;
		}

		$prevent = true;
		$this->tableSchema = Table::table($this->table);
		$prevent = false;

		return $this->tableSchema;
	}

	private function isTableDesigner( string $tableName, & $designer ): bool
	{
		if(strpos($tableName, '_SchemeDesigner') === false)
		{
			return false;
		}

		try {
			$designer = new ReflectionClass( $tableName );
		}
		catch( ReflectionException $e ) {
			$designer = null;
			return false;
		}

		if(! $designer->isSubclassOf(SchemeDesigner::class))
		{
			throw new InvalidArgumentException("Invalid scheme designer class " . $designer->getName());
		}

		return true;
	}

	protected function table( string $tableName, ?string $tableAlias = null )
	{
		$loadDesigner = $this->isTableDesigner($tableName, $designer);
		if( $loadDesigner )
		{
			$this->designer = $designer;
			$tableName = $this->designer->getMethod('getTableName')->invoke(null);
		}

		$fullTableName = strpos($tableName, "(") === false ? $this->connection->getTableName($tableName) : $tableName;
		$this->table = $tableName;
		$this->tableAlias = $tableAlias;

		$this
			->builder
			->from(
				$fullTableName, $tableAlias
			);

		if( $loadDesigner )
		{
			$schema = $this->designer->getMethod('getSchemaBuilder')->invoke(null);

			if( ! empty($schema["alias"]) )
			{
				$tableAlias = $schema["alias"];
				$this
					->builder
					->resetQueryPart('from')
					->from(
						$fullTableName, $tableAlias
					);
			}

			if( empty($tableAlias) )
			{
				$tableAlias = $fullTableName;
			}

			// add rename columns
			if( isset($schema['columns']) && is_array($schema['columns']) )
			{
				$this->columns = $schema['columns'];
			}
			else if($tableAlias != $fullTableName)
			{
				$ts = $this->getTableSchema();
				if($ts)
				{
					$prefix = $tableAlias . ".";
					foreach($ts->getColumnNames() as $name)
					{
						$this->columns[$name] = $prefix . $name;
					}
				}
			}

			// add select columns
			if( ! empty($schema['select']) )
			{
				$this->select($schema['select']);
			}

			// add joins
			if( ! empty($schema['joins']) )
			{
				$index = 1;

				// $join : tableName, type (left), criteria[]

				foreach($schema['joins'] as $join)
				{
					$prefix = $join["alias"] ?? 'tbl_' . ($index ++);
					$type = $join["type"] ?? 'left';
					$joinTableName = $join["tableName"];
					$criteria = $join["criteria"] ?? ($tableAlias . '.id = ' . $prefix . '.id');

					$this->addJoin($type, $tableAlias, $joinTableName, $prefix, $criteria);
				}
			}

			// add criteria
			if( ! empty($schema['criteria']) )
			{
				$this->where($schema['criteria']);
			}

			// add order
			if( ! empty($schema['orderBy']) )
			{
				$orderBy = Arr::wrap($schema['orderBy']);
				$first = false;

				foreach($orderBy as $sort => $sortDir)
				{
					if( is_int($sort) )
					{
						$sort = $sortDir;
						$sortDir = "ASC";
					}
					else
					{
						$sortDir = strtoupper($sortDir);
					}

					if($sortDir !== "DESC")
					{
						$sortDir = "ASC";
					}

					if($first)
					{
						$this->orderBy($sort, $sortDir);
						$first = false;
					}
					else
					{
						$this->addOrderBy($sort, $sortDir);
					}
				}
			}

			// add group
			if( ! empty($schema['groupBy']) )
			{
				$this->groupBy($schema['groupBy']);
			}
		}

		return $this;
	}

	public function getColumn(string $name): string
	{
		return $this->columns[$name] ?? $name;
	}

	public function where($name, $operator = null, $value = null, & $bindName = null )
	{
		return $this->criteria('where', $name, $operator, $value, $bindName);
	}

	/**
	 * Filter unique identify row.
	 *
	 * @param $value
	 * @param string $column
	 * @param null $bindName
	 * @return $this
	 */
	public function whereId($value, $column = 'id', & $bindName = null)
	{
		return $this
			->limit(1)
			->where($column, CriteriaBuilder::EQ, (int) $value, $bindName);
	}

	public function having($name, $operator = null, $value = null, & $bindName = null )
	{
		return $this->criteria('having', $name, $operator, $value, $bindName);
	}

	protected function criteria( $type, $name, $operator, $value, & $bindName = null )
	{
		if( $name instanceof CriteriaBuilder )
		{
			if(! isset($this->{$type}))
			{
				$this->{$type} = $name;
				$this->builder->add($type, $this->{$type});
			}
			else
			{
				$this->{$type}->raw($name);
			}

			return $this;
		}

		if( ! isset($this->{$type}) )
		{
			$this->{$type} = $this->newCriteria();
			$this->builder->add($type, $this->{$type});
		}

		/** @var CriteriaBuilder $criteria */
		$criteria = $this->{$type};

		if( $name instanceof Closure )
		{
			$name($criteria);
		}
		else if( is_array($name) )
		{
			$criteria->each($name);
		}
		else
		{
			if( is_null($value) )
			{
				$value = $operator;
				$operator = CriteriaBuilder::EQ;
			}

			$criteria->add($name, $operator, $value, $bindName);
		}

		return $this;
	}

	/**
	 * Set the "limit" value of the query.
	 *
	 * @param  int $limit
	 * @param  int $offset
	 * @return $this
	 */
	public function limit( int $limit, ? int $offset = null )
	{
		$this
			->builder
			->setMaxResults( max(1, $limit));

		if( ! is_null($offset) )
		{
			$this->offset($offset);
		}

		return $this;
	}

	/**
	 * Set the "offset" value of the query.
	 *
	 * @param  int  $value
	 * @return $this
	 */
	public function offset(int $value)
	{
		$this
			->builder
			->setFirstResult( max(0, $value) );

		return $this;
	}

	/**
	 * Set the limit and offset for a given page.
	 *
	 * @param  int  $page
	 * @param  int  $perPage
	 * @return Builder
	 */
	public function forPage(int $page, int $perPage = 15)
	{
		if( $page < 1 )
		{
			$page = 1;
		}
		if( $perPage < 1 )
		{
			$perPage = 1;
		}
		return $this->limit($perPage, $page > 1 ? ($page - 1) * $perPage : 0);
	}

	/**
	 * Specifies an ordering for the query results.
	 * Replaces any previously specified orderings, if any.
	 *
	 * @param string $sort  The ordering expression.
	 * @param string $order The ordering direction.
	 *
	 * @return $this This Builder instance.
	 */
	public function orderBy(string $sort, ?string $order = null)
	{
		$this->builder->orderBy($this->getColumn($sort), $order);
		return $this;
	}

	/**
	 * Adds an ordering to the query results.
	 *
	 * @param string $sort  The ordering expression.
	 * @param string $order The ordering direction.
	 *
	 * @return $this This Builder instance.
	 */
	public function addOrderBy(string $sort, ?string$order = null)
	{
		$this->builder->addOrderBy($this->getColumn($sort), $order);
		return $this;
	}

	/**
	 * Specifies a grouping over the results of the query.
	 * Replaces any previously specified groupings, if any.
	 *
	 * @param mixed $groupBy The grouping expression.
	 *
	 * @return $this This Builder instance.
	 */
	public function groupBy($groupBy)
	{
		if(is_array($groupBy))
		{
			foreach($groupBy as & $column)
			{
				$column = $this->getColumn((string) $column);
			}
		}
		else
		{
			$groupBy = $this->getColumn((string) $groupBy);
		}
		$this->builder->groupBy($groupBy);
		return $this;
	}

	/**
	 * Adds a grouping expression to the query.
	 *
	 * @param string $groupBy The grouping expression.
	 *
	 * @return $this This Builder instance.
	 */
	public function addGroupBy(string $groupBy)
	{
		$this->builder->addGroupBy( $this->getColumn($groupBy) );
		return $this;
	}

	public function join($fromAlias, $join, $alias, $condition = null, array $rename = [])
	{
		return $this->addJoin('inner', $fromAlias, $join, $alias, $condition, $rename );
	}

	public function innerJoin($fromAlias, $join, $alias, $condition = null, array $rename = [])
	{
		return $this->addJoin('inner', $fromAlias, $join, $alias, $condition, $rename );
	}

	public function leftJoin($fromAlias, $join, $alias, $condition = null, array $rename = [])
	{
		return $this->addJoin('left', $fromAlias, $join, $alias, $condition, $rename );
	}

	public function rightJoin($fromAlias, $join, $alias, $condition = null, array $rename = [])
	{
		return $this->addJoin('right', $fromAlias, $join, $alias, $condition, $rename );
	}

	/**
	 * Create new criteria for this builder
	 *
	 * @param string $type
	 * @return CriteriaBuilder
	 */
	public function newCriteria( $type = CriteriaBuilder::TYPE_AND )
	{
		return new CriteriaBuilder( $this, $type );
	}

	protected function addJoin( $type, $fromAlias, $join, $alias, $condition = null, $rename = [] )
	{
		static $prevent = false;

		if( $condition instanceof Closure )
		{
			$criteria = $this->newCriteria();
			$condition($criteria);
			$condition = $criteria->getSQL();
		}
		else if( is_array($condition) )
		{
			$criteria = $this->newCriteria();
			$criteria->each($condition);
			$condition = $criteria->getSQL();
		}

		$joinTableName = $join;

		$loadDesigner = $this->isTableDesigner($joinTableName, $designer);
		if( $loadDesigner )
		{
			/** @var ReflectionClass $designer */
			$joinTableName = $designer->getMethod('getTableName')->invoke(null);
		}

		if( ! $prevent && App::getInstance()->isInstall() )
		{
			$prevent = true;
			$tableSchema = Table::table($joinTableName);
			$prevent = false;

			$prefix = $alias . ".";
			foreach($tableSchema->getColumnNames() as $name)
			{
				$joinName = $rename[$name] ?? $name;
				if( !isset($this->columns[$joinName]) )
				{
					$selectName = $prefix . $name;
					$this->columns[$joinName] = $selectName;
					if($joinName !== $name)
					{
						$this->columnsSelect[$joinName] = $selectName . " AS " . $joinName;
					}
				}
			}
		}

		$part = [
			$fromAlias => [
				'joinType'      => $type,
				'joinTable'     => $this->connection->getTableName($joinTableName),
				'joinAlias'     => $alias,
				'joinCondition' => $condition,
			],
		];

		$this
			->builder
			->add('join', $part, true);

		return $this;
	}

	/**
	 * Gets the complete SQL string formed by the current specifications of this Builder.
	 *
	 * @return string The SQL query string.
	 */
	public function getSql(): string
	{
		$builder = $this->builder;
		$isSelectType = $builder->getType() === DbalQueryBuilder::SELECT;

		// fix empty select
		if($isSelectType && empty( $builder->getQueryPart('select') ) )
		{
			$this->select();
		}

		$sql = $this->builder->getSQL();
		if($isSelectType && $this->distinct)
		{
			$sql = Str::replaceFirst("SELECT ", "SELECT DISTINCT", $sql);
		}

		return $sql;
	}

	protected function getSelectColumns($columns)
	{
		if( ! is_array($columns) )
		{
			$columns = [$columns];
		}

		$map = [];
		foreach($columns as $column)
		{
			if(isset($this->columnsSelect[$column]))
			{
				$map[] = $this->columnsSelect[$column];
			}
			else if(isset($this->columns[$column]))
			{
				$map[] = $this->columns[$column];
			}
			else
			{
				$map[] = $column;
			}
		}

		return $map;
	}

	/**
	 * Set the columns to be selected.
	 *
	 * @param  array|mixed  $columns
	 * @return $this
	 */
	public function select($columns = ['*'])
	{
		$this->builder->select($this->getSelectColumns($columns));
		return $this;
	}

	/**
	 * Set distinct mode.
	 * Force the query to only return distinct results.
	 *
	 * @param bool $value
	 * @return $this
	 */
	public function setDistinct(bool $value = true)
	{
		$this->distinct = $value;
		return $this;
	}

	/**
	 * Adds an item that is to be returned in the query result.
	 *
	 * @param mixed $select The selection expression.
	 *
	 * @return $this This Builder instance.
	 */
	public function addSelect($select)
	{
		$this->builder->addSelect($this->getSelectColumns($select));
		return $this;
	}

	protected function getDesignerReflector(): ReflectionClass
	{
		if( ! isset($this->designer) )
		{
			$this->designer = new ReflectionClass(SchemeDesigner::class );
		}
		return $this->designer;
	}

	protected function checkTableExists()
	{
		if( empty($this->table) )
		{
			throw new InvalidArgumentException("Unknown query table");
		}
	}

	/**
	 * @param string|null $column
	 * @return false|mixed
	 * @throws DBALException
	 */
	public function value(?string $column = null)
	{
		$this->checkTableExists();

		if($column)
		{
			$this->select($this->getColumn($column));
		}

		$builder = $this->builder;
		$builder->setMaxResults(1);

		return $this
			->getDbalConnection()
			->fetchColumn(
				$builder->getSQL(),
				$builder->getParameters(),
				0,
				$builder->getParameterTypes()
			);
	}

	/**
	 * @return bool|object|SchemeDesigner
	 * @throws DBALException
	 */
	public function first()
	{
		$this->checkTableExists();

		$builder = $this->builder;
		$builder->setMaxResults(1);

		$con = $this->getDbalConnection();

		$row = $con->fetchAssoc(
			$builder->getSQL(),
			$builder->getParameters(),
			$builder->getParameterTypes()
		);

		return $row ? $this->getDesignerReflector()->newInstance($row, $this->connection) : false;
	}

	/**
	 * @return Collection|SchemeDesigner[]
	 */
	public function get()
	{
		return $this->project(function(array $row) {
			return $this->getDesignerReflector()->newInstance($row, $this->connection);
		});
	}

	/**
	 * @param Closure $closure
	 * @param string|null $keyName
	 * @return Collection
	 */
	public function project(Closure $closure, ?string $keyName = null)
	{
		$this->checkTableExists();

		$builder = $this->builder;

		$rows = [];
		$stmt = $this
			->getConnection()
			->executeQuery(
				$builder->getSQL(),
				$builder->getParameters(),
				$builder->getParameterTypes()
			);

		$useKey = $keyName !== null && strlen($keyName) > 0;

		while($row = $stmt->fetch())
		{
			if($useKey)
			{
				$rows[$row[$keyName]] = $closure($row);
			}
			else
			{
				$rows[] = $closure($row);
			}
		}

		$stmt->closeCursor();

		return new Collection($rows);
	}

	public function update( $data = null )
	{
		$this->checkTableExists();

		$builder = $this->builder;

		return $this
			->getConnection()
			->executeWritable(
				$this->getUpdateSql( $data ),
				$builder->getParameters(),
				$builder->getParameterTypes()
			);
	}

	public function insert( $data = null )
	{
		$this->checkTableExists();

		$builder = $this->builder;

		return $this
			->getConnection()
			->executeWritable(
				$this->getInsertSql( $data ),
				$builder->getParameters(),
				$builder->getParameterTypes()
			);
	}

	public function lastInsertId($seqName = null)
	{
		return $this->getDbalConnection()->lastInsertId($seqName);
	}

	public function delete()
	{
		$this->checkTableExists();

		$builder = $this->builder;

		return $this
			->getDbalConnection()
			->executeUpdate(
				$this->getDeleteSql(),
				$builder->getParameters(),
				$builder->getParameterTypes()
			);
	}

	public function getInsertState(): StateInsertBuilder
	{
		if( ! isset($this->insert) )
		{
			$this->insert = new StateInsertBuilder($this);
		}

		return $this->insert;
	}

	public function getInsertSql( $data = null )
	{
		$insert = $this->getInsertState();

		if( is_array($data) )
		{
			$insert->values($data);
		}
		else if( $data instanceof Closure )
		{
			$data($insert);
		}

		$insert->complete();

		return $this->builder->insert($this->getFullTableWritableName())->getSQL();
	}

	/**
	 * @return StateUpdateBuilder
	 */
	public function getUpdateState(): ?StateUpdateBuilder
	{
		if( ! isset($this->update) )
		{
			$this->update = new StateUpdateBuilder($this);
		}

		return $this->update;
	}

	public function getUpdateSql( $data = null )
	{
		$update = $this->getUpdateState();

		if( is_array($data) )
		{
			$update->values($data);
		}
		else if( $data instanceof Closure )
		{
			$data($update);
		}

		$update->complete();

		return $this->builder->update($this->getFullTableWritableName())->getSQL();
	}

	public function getDeleteSql()
	{
		return $this->builder->delete($this->getFullTableWritableName())->getSQL();
	}

	protected function getFullTableWritableName()
	{
		$table = $this->table;
		if(strpos($table, "(") !== false)
		{
			throw new InvalidArgumentException("Invalid writable query table name '{$this->table}'");
		}
		return $this->connection->getTableName($table);
	}

	/**
	 * Gets a string representation of this Builder which corresponds to
	 * the final SQL query being constructed.
	 *
	 * @return string The string representation of this Builder.
	 */
	public function __toString()
	{
		return $this->getSql();
	}

	/**
	 * Deep clone of all expression objects in the SQL parts.
	 *
	 * @return void
	 */
	public function __clone()
	{
		$this->builder = clone $this->builder;

		foreach(['where', 'having', 'update', 'insert'] as $name)
		{
			if( isset($this->{$name}) )
			{
				$object = $this->{$name};
				if( $object instanceof AbstractBuilderContainer )
				{
					$this->{$name} = $object->makeClone($this);
				}
				else
				{
					$this->{$name} = null;
				}
			}
		}
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
		return $this->aggregate(__FUNCTION__, $column );
	}

	/**
	 * Retrieve the minimum value of a given column.
	 *
	 * @param  string|null  $column
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function min( ?string $column = null ): int
	{
		return $this->aggregate(__FUNCTION__, $column );
	}

	/**
	 * Retrieve the maximum value of a given column.
	 *
	 * @param  string|null  $column
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function max( ?string $column = null ): int
	{
		return $this->aggregate(__FUNCTION__, $column );
	}

	/**
	 * Retrieve the sum of the values of a given column.
	 *
	 * @param  string|null  $column
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function sum( ?string $column = null ): int
	{
		return $this->aggregate(__FUNCTION__, $column );
	}

	/**
	 * Retrieve the average of the values of a given column.
	 *
	 * @param  string|null $column
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function avg( ?string $column = null ): int
	{
		return $this->aggregate(__FUNCTION__, $column );
	}

	/**
	 * Execute an aggregate function on the database.
	 *
	 * @param  string $function
	 * @param  string $column
	 *
	 * @return int
	 *
	 * @throws DBALException
	 */
	protected function aggregate($function, $column): int
	{
		$this->checkTableExists();

		if( empty($column) )
		{
			$column = "*";
		}

		$builder = clone $this->builder;

		$fast = empty( $builder->getQueryPart('groupBy') ) && empty( $builder->getQueryPart('having') );
		if($fast)
		{
			$builder
				->resetQueryParts(['from', 'orderBy'])
				->from(
					$this->getConnection()->getTableName($this->table), $this->tableAlias
				);
		}
		else
		{
			$builder
				->resetQueryParts(['orderBy']);
		}

		$builder
			->setMaxResults(null)
			->setFirstResult(null);

		if( empty( $builder->getQueryPart('groupBy') ) && empty( $builder->getQueryPart('having') ) )
		{
			$sql = $builder
				->select(strtoupper($function) . '(' . $column . ')')
				->getSQL();
		}

		// Once we have run the pagination count query, we will get the resulting count and
		// take into account what type of query it was. When there is a group by we will
		// just return the count of the entire results set since that will be correct.
		else
		{
			$countColumn = "*";

			if( strpos($column, '*') === false )
			{
				$column .= ' AS uid';
				$countColumn = 'uid';
			}

			$sql = "SELECT COUNT(table_count.{$countColumn}) FROM (" . $builder->select($column)->getSQL() . ") AS table_count";
		}

		// update binding values, remove not used parameters

		$oldBindings = $builder->getParameters();
		$oldTypes = $builder->getParameterTypes();
		$bindings = [];
		$types = [];

		foreach( $oldBindings as $name => $value )
		{
			if( strpos($sql, ":" . $name) !== false )
			{
				$bindings[$name] = $value;
				if( isset($oldTypes[$name]) )
				{
					$types[$name] = $oldTypes[$name];
				}
			}
		}

		$value = $this->getDbalConnection()->fetchColumn($sql, $bindings, 0, $types);

		return $value ? (int) $value : 0;
	}

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param  string  $column
	 * @return Builder
	 */
	public function latest($column = 'created_at')
	{
		return $this->orderBy($column, 'DESC');
	}

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param  string  $column
	 * @return Builder
	 */
	public function oldest($column = 'created_at')
	{
		return $this->orderBy($column, 'ASC');
	}
}