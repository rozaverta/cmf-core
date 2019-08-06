<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 17:05
 */

namespace RozaVerta\CmfCore\Traits;

use ArrayAccess;

/**
 * @property array $items
 */
trait ComparatorTrait
{
	public function equiv( string $name, $value, bool $strict = true ): bool
	{
		if( ! array_key_exists($name, $this->items) )
		{
			return false;
		}

		if( $strict )
		{
			return $this->items[$name] === $value;
		}
		else
		{
			return $this->items[$name] == $value;
		}
	}

	public function inItems( $value, bool $strict = null ): bool
	{
		return in_array( $value, $this->items, $strict );
	}

	public function inArray( string $name, $assoc, bool $strict = false ): bool
	{
		if( ! array_key_exists($name, $this->items) )
		{
			return false;
		}
		else
		{
			return in_array( $this->items[$name], (array) $assoc, $strict );
		}
	}

	public function inZero( string $name, string $operator = "=", bool $strict = false ): bool
	{
		if( ! isset( $this->items[$name] ) ||
			$strict && ! is_int( $this->items[$name] ) ||
			! $strict && ! is_numeric( $this->items[$name] ) ) {
			return false;
		}

		$value = (int) $this->items[$name];

		switch( $operator ) {
			case "="  :
			case "==" : return $value === 0;
			case "<"  : return $value < 0;
			case "<=" : return $value <= 0;
			case ">"  : return $value > 0;
			case ">=" : return $value >= 0;
			case "!=" :
			case "!"  : return $value !== 0;
		}

		return false;
	}

	public function interval( string $name, $min, $max, $strict = false ): bool
	{
		if( ! isset( $this->items[$name] ) ||
			$strict && ! is_int( $this->items[$name] ) ||
			! $strict && ! is_numeric( $this->items[$name] ) ) {
			return false;
		}

		$value = (int) $this->items[$name];

		return $value >= $min && $value <= $max;
	}

	public function isArray( string $name ): bool
	{
		return isset( $this->items[$name] ) && (is_array( $this->items[$name] ) || $this->items[$name] instanceof ArrayAccess);
	}

	public function isInt( string $name ): bool
	{
		return isset( $this->items[$name] ) && is_int( $this->items[$name] );
	}

	public function isBool( string $name ): bool
	{
		return isset( $this->items[$name] ) && is_bool( $this->items[$name] );
	}

	public function isNumeric( string $name ): bool
	{
		return isset( $this->items[$name] ) && is_numeric( $this->items[$name] );
	}

	public function isFill( string $name ): bool
	{
		if( !isset( $this->items[$name] ) )
		{
			return false;
		}
		if( is_string($this->items[$name]) )
		{
			return strlen(trim($this->items[$name])) > 0;
		}
		else
		{
			return ! empty($this->items[$name]);
		}
	}
}