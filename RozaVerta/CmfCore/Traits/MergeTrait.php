<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.08.2019
 * Time: 15:01
 */

namespace RozaVerta\CmfCore\Traits;

use RozaVerta\CmfCore\Interfaces\TypeOfInterface;

/**
 * Trait MergeTrait
 *
 * @property array $items
 *
 * @package RozaVerta\CmfCore\Traits
 */
trait MergeTrait
{
	abstract public function setData( array $data );

	abstract public function offsetSet( $offset, $value );

	abstract public function offsetExists( $offset );

	/**
	 * Merge array data
	 *
	 * @param array $items
	 * @param bool  $update
	 *
	 * @return $this
	 */
	public function merge( array $items, bool $update = false )
	{
		if( !count( $this->items ) )
		{
			$this->setData( $items );
		}
		else if( $update )
		{
			if( $this instanceof TypeOfInterface )
			{
				foreach( $items as $key => $value )
				{
					$this->offsetSet( $key, $value );
				}
			}
			else
			{
				$this->items = array_merge( $this->items, $items );
			}
		}
		else
		{
			foreach( $items as $key => $value )
			{
				if( !$this->offsetExists( $key ) )
				{
					$this->offsetSet( $key, $value );
				}
			}
		}

		return $this;
	}
}