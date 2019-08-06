<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.06.2019
 * Time: 14:15
 */

namespace RozaVerta\CmfCore\Database\Interfaces;

use Closure;
use RozaVerta\CmfCore\Database\Query\WriteableState;

/**
 * Interface FetchBuilderInterface
 *
 * @package RozaVerta\CmfCore\Database\Interfaces
 */
interface WriteBuilderInterface extends FetchBuilderInterface, JoinBuilderInterface
{
	/**
	 * Update a record in the database.
	 *
	 * @param null|array|Closure $data
	 *
	 * @return int
	 */
	public function update( $data = null ): int;

	/**
	 * Insert a new record into the database.
	 *
	 * @param null|array|Closure $data
	 *
	 * @return int
	 */
	public function insert( $data = null ): int;

	/**
	 * Insert a new record and get the value of the primary key.
	 *
	 * @param null|array|Closure $data
	 *
	 * @return string
	 */
	public function insertGetId( $data = null );

	/**
	 * Delete a record from the database.
	 *
	 * @return int
	 */
	public function delete(): int;

	/**
	 * Get the value of the primary key.
	 *
	 * @param null $seqName
	 *
	 * @return string
	 */
	public function lastInsertId( $seqName = null );

	/**
	 * Get WriteableState object.
	 *
	 * @return WriteableState
	 */
	public function getState(): WriteableState;
}