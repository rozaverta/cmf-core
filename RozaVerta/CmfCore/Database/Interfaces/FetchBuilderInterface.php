<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.06.2019
 * Time: 14:15
 */

namespace RozaVerta\CmfCore\Database\Interfaces;

use Closure;
use Doctrine\DBAL\Connection as DbalConnection;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;
use RozaVerta\CmfCore\Database\Scheme\Table;
use RozaVerta\CmfCore\Support\Collection;

/**
 * Interface FetchBuilderInterface
 *
 * @package RozaVerta\CmfCore\Database\Interfaces
 */
interface FetchBuilderInterface
{
	/**
	 * Get Connection object
	 *
	 * @return Connection
	 */
	public function getConnection(): Connection;

	/**
	 * Get Doctrine Connection object
	 *
	 * @return DbalConnection
	 */
	public function getDbalConnection(): DbalConnection;

	/**
	 * Get base table name, without prefix
	 *
	 * @return string
	 */
	public function getTableName(): string;

	/**
	 * Get Table schema for base query table
	 *
	 * @return Table
	 */
	public function getTableSchema(): Table;

	/**
	 * Get full column name
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function getColumn( string $name ): string;

	/**
	 * Set distinct mode.
	 * Force the query to only return distinct results.
	 *
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function setDistinct( bool $value = true );

	/**
	 * Adds an item that is to be returned in the query result.
	 *
	 * @param mixed $select The selection expressions.
	 *
	 * @return $this
	 */
	public function select( $select = [ '*' ] );

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
	public function where( $name, $operator = null, $value = null, & $bindName = null );

	/**
	 * Filter unique identify row.
	 *
	 * @param        $value
	 * @param string $column
	 * @param null   $bindName
	 *
	 * @return $this
	 */
	public function whereId( $value, $column = 'id', & $bindName = null );

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
	public function having( $name, $operator = null, $value = null, & $bindName = null );

	/**
	 * Set the "limit" value of the query.
	 *
	 * @param int $limit
	 * @param int $offset
	 *
	 * @return $this
	 */
	public function limit( int $limit, ? int $offset = null );

	/**
	 * Set the "offset" value of the query.
	 *
	 * @param int $value
	 * @return $this
	 */
	public function offset( int $value );

	/**
	 * Set the limit and offset for a given page.
	 *
	 * @param int $page
	 * @param int $perPage
	 *
	 * @return $this
	 */
	public function forPage( int $page, int $perPage = 15 );

	/**
	 * Adds an ordering to the query results.
	 *
	 * @param string $sort  The ordering expression.
	 * @param string $order The ordering direction.
	 *
	 * @return $this
	 */
	public function orderBy( string $sort, ?string $order = null );

	/**
	 * Add an "order by random()" clause to the query.
	 *
	 * @param string|null $seed
	 *
	 * @return $this
	 */
	public function orderByRandom( ?string $seed = null );

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param string $column
	 *
	 * @return $this
	 */
	public function latest( string $column = 'created_at' );

	/**
	 * Add an "order by" clause for a timestamp to the query.
	 *
	 * @param string $column
	 *
	 * @return $this
	 */
	public function oldest( string $column = 'created_at' );

	/**
	 * Adds a grouping expression to the query.
	 *
	 * @param mixed $groupBy The grouping expression.
	 *
	 * @return $this This DeprecatedBuilder instance.
	 */
	public function groupBy( $groupBy );

	/**
	 * Check rows exists in current query
	 *
	 * @return bool
	 */
	public function exists(): bool;

	/**
	 * Get the first value of the first row
	 *
	 * @param string|null $column
	 *
	 * @return false|mixed
	 */
	public function value( string $column );

	/**
	 * Get the first row result SchemeDesigner object
	 *
	 * @return SchemeDesigner
	 */
	public function first();

	/**
	 * Get collection object as SchemeDesigner items
	 *
	 * @return Collection|SchemeDesigner[]
	 */
	public function get();

	/**
	 * Get custom collection result query
	 *
	 * @param Closure     $closure
	 * @param string|null $keyName
	 *
	 * @return Collection
	 */
	public function project( Closure $closure, ?string $keyName = null );

	/**
	 * Retrieve the "count" result of the query.
	 *
	 * @param string|null $column
	 *
	 * @return int
	 */
	public function count( ?string $column = null ): int;

	/**
	 * Retrieve the minimum value of a given column.
	 *
	 * @param string|null $column
	 *
	 * @return mixed
	 */
	public function min( ?string $column = null ): int;

	/**
	 * Retrieve the maximum value of a given column.
	 *
	 * @param string|null $column
	 *
	 * @return mixed
	 */
	public function max( ?string $column = null ): int;

	/**
	 * Retrieve the sum of the values of a given column.
	 *
	 * @param string|null $column
	 *
	 * @return mixed
	 */
	public function sum( ?string $column = null ): int;

	/**
	 * Retrieve the average of the values of a given column.
	 *
	 * @param string|null $column
	 *
	 * @return mixed
	 */
	public function avg( ?string $column = null ): int;
}