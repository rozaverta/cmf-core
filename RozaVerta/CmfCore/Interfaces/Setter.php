<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2019
 * Time: 15:57
 */

namespace RozaVerta\CmfCore\Interfaces;

/**
 * Interface Setter
 *
 * @package RozaVerta\CmfCore\Interfaces
 */
interface Setter
{
	/**
	 * Set the item at a given offset.
	 *
	 * @param string $name
	 * @param        $value
	 *
	 * @return mixed
	 */
	public function set( string $name, $value );

	/**
	 * Unset the item at a given offset.
	 *
	 * @param string $name
	 *
	 * @return $this
	 */
	public function setNull( string $name );

	/**
	 * Set items, remove old values.
	 *
	 * @param array $data
	 *
	 * @return $this
	 */
	public function setData( array $data );

	/**
	 * Remove an item from the collection by key.
	 *
	 * @param string|array $keys
	 * @return $this
	 */
	public function forget( $keys );

	/**
	 * Remove all items from the collection.
	 *
	 * @return $this
	 */
	public function forgetAll();
}