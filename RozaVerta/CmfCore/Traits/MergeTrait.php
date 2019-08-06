<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.08.2019
 * Time: 15:01
 */

namespace RozaVerta\CmfCore\Traits;

use RozaVerta\CmfCore\Workshops\Module\ConfigFile;

/**
 * Trait MergeTrait
 *
 * @property array $items
 *
 * @package RozaVerta\CmfCore\Traits
 */
trait MergeTrait
{
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
			$this->items = $items;
		}
		else if( $update )
		{
			$this->items = array_merge( $this->items, $items );
		}
		else
		{
			foreach( $items as $key => $value )
			{
				if( !$this->offsetExists( $key ) )
				{
					$this->items[$key] = $value;
				}
			}
		}

		return $this;
	}
}