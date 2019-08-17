<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2019
 * Time: 15:57
 */

namespace RozaVerta\CmfCore\Interfaces;

/**
 * Interface Getter
 *
 * @package RozaVerta\CmfCore\Interfaces
 */
interface Getter
{
	/**
	 * Get an item from the collection by key.
	 *
	 * @param mixed $name
	 * @param null  $default
	 *
	 * @return mixed
	 */
	public function get( string $name, $default = null );

	/**
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function getAll(): array;

	/**
	 * Get result as array.
	 *
	 * @param string $name
	 * @return array
	 */
	public function getArray( string $name ): array;

	/**
	 * Get an item from the collection by key or get alternate value (then) after the match test.
	 *
	 * @param string                     $name
	 * @param                            $then
	 * @param bool|string|array|\Closure $test
	 *
	 * @return mixed
	 */
	public function then( string $name, $then, $test );

	/**
	 * Get an item from an array or object using "dot" notation.
	 *
	 * @param      $name
	 * @param null $default
	 *
	 * @return mixed
	 */
	public function fetch( $name, $default = null );

	/**
	 * Get an item from the collection by keys.
	 *
	 * @param array $keys
	 * @param mixed $default default value
	 *
	 * @return mixed
	 */
	public function choice( array $keys, $default = null );

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param string | array $name
	 * @return bool
	 */
	public function has( $name ): bool;
}