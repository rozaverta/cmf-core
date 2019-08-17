<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 2:59
 */

namespace RozaVerta\CmfCore\Traits;

use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Helper\Data;
use RozaVerta\CmfCore\Interfaces\Arrayable;

/**
 * @property array $items
 */
trait GetTrait
{
	/**
	 * Get an item from the collection by key.
	 *
	 * @param mixed $name
	 * @param null  $default
	 *
	 * @return mixed
	 */
	public function get( string $name, $default = null )
	{
		return $this->offsetExists( $name ) ? $this->items[$name] : $default;
	}

	/**
	 * Get an item from the collection by key or get alternate value (then) after the match test
	 *
	 * @param string $name
	 * @param $then
	 * @param bool|string|array|\Closure $test
	 * @return mixed
	 */
	public function then( string $name, $then, $test )
	{
		if( ! $this->offsetExists($name) )
		{
			return $then;
		}

		$value = $this->items[$name];

		if( $test instanceof \Closure )
		{
			return $test($value) ? $value : $then;
		}
		else if( is_array($test) )
		{
			return in_array($value, $test, true) ? $value : $then;
		}
		else if( is_bool($test) )
		{
			return $test ? $value : $then;
		}
		else
		{
			return $test === $value ? $value : $then;
		}
	}

	/**
	 * Get an item from an array or object using "dot" notation.
	 *
	 * @param      $name
	 * @param null $default
	 *
	 * @return mixed
	 */
	public function fetch( $name, $default = null )
	{
		return is_string($name) && strpos($name, ".") === false
			? $this->get($name)
			: Data::getIn( $this->items, $name, $default );
	}

	/**
	 * Get an item from an array or object using "dot" notation or default value if not exists.
	 *
	 * @param $name
	 * @param $default
	 * @return mixed
	 * @deprecated
	 */
	public function fetchOr( $name, $default )
	{
		return is_string($name) && strpos($name, ".") === false
			? $this->get( $name, $default )
			: Data::getIn($this->items, $name, $default);
	}

	/**
	 * Get an item from the collection by keys.
	 *
	 * @param array $keys
	 * @param mixed $default default value
	 *
	 * @return mixed
	 */
	public function choice( array $keys, $default = null )
	{
		foreach( $keys as $key )
		{
			if( $this->offsetExists($key) )
			{
				return $this->items[$key];
			}
		}

		return $default;
	}

	/**
	 * Get all of the items in the collection.
	 *
	 * @return array
	 */
	public function getAll(): array
	{
		return $this->items;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->items;
	}

	/**
	 * Get an item from the collection by key or default value if not exists.
	 *
	 * @param  mixed  $name
	 * @param  mixed  $default_value
	 * @return mixed
	 * @deprecated
	 */
	public function getOr( $name, $default_value )
	{
		if ($this->offsetExists($name))
		{
			return $this->items[$name];
		}
		return Data::value($default_value);
	}

	/**
	 * Get result as array
	 *
	 * @param string $name
	 * @return array
	 */
	public function getArray( string $name ): array
	{
		if( ! $this->offsetExists($name) )
		{
			return [];
		}

		$value = $this->items[$name];
		if( $value instanceof Arrayable )
		{
			return $value->toArray();
		}
		if( $value instanceof \Traversable )
		{
			return iterator_to_array($value);
		}
		if( is_object($value) )
		{
			return get_object_vars($value);
		}

		return Arr::wrap($value);
	}

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param string | array $name
	 * @return bool
	 */
	public function has( $name ): bool
	{
		if( !is_array( $name ) )
		{
			if( func_num_args() == 1 )
			{
				return $this->offsetExists( $name );
			}
			else
			{
				$name = func_get_args();
			}
		}

		foreach( $name as $value )
		{
			if( !$this->offsetExists( $value ) )
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Determine if an item exists in the collection by key.
	 *
	 * @param  $args
	 * @return bool
	 * @deprecated
	 */
	public function getIs( ... $args )
	{
		return $this->has( ... $args );
	}

	/**
	 * Get an item at a given offset.
	 *
	 * @param  mixed  $offset
	 * @return mixed
	 */
	public function & offsetGet( $offset )
	{
		if( ! isset($this->items[$offset]) )
		{
			$value = null;
		}

		else if( is_array($this->items[$offset]) )
		{
			return $this->items[$offset];
		}

		else
		{
			$value = $this->items[$offset];
		}

		return $value;
	}

	/**
	 * Determine if an item exists at an offset.
	 *
	 * @param  mixed  $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return array_key_exists($offset, $this->items);
	}
}