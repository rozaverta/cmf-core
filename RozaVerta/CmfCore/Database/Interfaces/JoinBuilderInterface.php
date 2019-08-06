<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.06.2019
 * Time: 16:08
 */

namespace RozaVerta\CmfCore\Database\Interfaces;

use Closure;

/**
 * Interface JoinBuilderInterface
 *
 * @package RozaVerta\CmfCore\Database\Interfaces
 */
interface JoinBuilderInterface
{
	public const JOIN_INNER = "inner";
	public const JOIN_LEFT = "left";
	public const JOIN_RIGHT = "right";

	/**
	 * Add table.
	 *
	 * @param string      $table
	 * @param string|null $alias
	 * @param array       $rename
	 *
	 * @return $this
	 */
	public function from( $table, ? string $alias = null, array $rename = [] );

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
	public function join( string $mode, string $table, string $alias, Closure $condition, array $rename = [] );

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
	public function innerJoin( string $table, string $alias, Closure $condition, array $rename = [] );

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
	public function leftJoin( string $table, string $alias, Closure $condition, array $rename = [] );

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
	public function rightJoin( string $table, string $alias, Closure $condition, array $rename = [] );
}