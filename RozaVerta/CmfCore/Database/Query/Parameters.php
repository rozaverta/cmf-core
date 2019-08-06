<?php

namespace RozaVerta\CmfCore\Database\Query;

use RozaVerta\CmfCore\Database\Expression;

/**
 * Class Parameters
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
class Parameters
{
	private $paramPrefix;

	private $paramCount = 0;

	private $parameters = [];

	private $types = [];

	private static $paramKey = 1;

	/**
	 * Parameters constructor.
	 */
	public function __construct()
	{
		$this->updatePrefix();
	}

	/**
	 * Parameter name exists
	 *
	 * @param string $name
	 * @param bool   $column
	 *
	 * @return bool
	 */
	public function has( string $name, bool $column = false )
	{
		$key = $this->name( $name, $column );
		return array_key_exists( $key, $this->parameters );
	}

	/**
	 * Delete saved parameter
	 *
	 * @param string $name
	 * @param bool   $column
	 */
	public function delete( string $name, bool $column = false ): void
	{
		$key = $this->name( $name, $column );
		unset( $this->parameters[$key], $this->types[$key] );
	}

	/**
	 * Get parameter value or NULL
	 *
	 * @param string $name
	 * @param bool   $column
	 *
	 * @return mixed|null
	 */
	public function value( string $name, bool $column = false )
	{
		$key = $this->name( $name, $column );
		return $this->parameters[$key] ?? null;
	}

	/**
	 * Get parameter type or NULL
	 *
	 * @param string $name
	 * @param bool   $column
	 *
	 * @return mixed|null
	 */
	public function type( string $name, bool $column = false )
	{
		$key = $this->name( $name, $column );
		return $this->types[$key] ?? null;
	}

	/**
	 * Get all parameter names
	 *
	 * @return array
	 */
	public function keys(): array
	{
		return array_keys( $this->parameters );
	}

	/**
	 * Get all parameters
	 *
	 * @return array
	 */
	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * Get reserved types
	 *
	 * @return array
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	/**
	 * Create parameter name from string
	 *
	 * @param string $name
	 * @param bool   $column
	 *
	 * @return string
	 */
	public function name( string $name, bool $column = false ): string
	{
		if( !strlen( $name ) )
		{
			return $this->nextName( $column );
		}
		else if( $name[0] === ":" )
		{
			return $name;
		}
		else
		{
			return $this->paramPrefix . ( $column ? "val_" : "con_" ) . $name;
		}
	}

	/**
	 * Create new parameter name automatically
	 *
	 * @param bool $column
	 *
	 * @return string
	 */
	public function nextName( bool $column = false ): string
	{
		return $this->paramPrefix . ( $column ? "val_n_" : "con_n_" ) . ( ++$this->paramCount );
	}

	/**
	 * Get the last automatically generated parameter name
	 *
	 * @param bool $column
	 *
	 * @return string|null
	 */
	public function lastName( bool $column = false ): ?string
	{
		return $this->paramCount < 1 ? null : ( $this->paramPrefix . ( $column ? "val_n_" : "con_n_" ) . $this->paramCount );
	}

	/**
	 * Add parameter value
	 *
	 * @param null $value
	 * @param null $type
	 *
	 * @return string
	 */
	public function bindNext( $value = null, $type = null ): string
	{
		return $this->_bind( $this->nextName(), $value, $type );
	}

	/**
	 * Add parameter value for column
	 *
	 * @param null $value
	 * @param null $type
	 *
	 * @return string
	 */
	public function bindNextForColumn( $value = null, $type = null ): string
	{
		return $this->_bind( $this->nextName( true ), $value, $type );
	}

	/**
	 * Add parameter value, create parameter name from string
	 *
	 * @param string $name
	 * @param null   $value
	 * @param null   $type
	 *
	 * @return string
	 */
	public function bind( string $name, $value = null, $type = null ): string
	{
		return $this->_bind( $this->name( $name ), $value, $type );
	}

	/**
	 * Add parameter value for column, create parameter name from string
	 *
	 * @param string $name
	 * @param null   $value
	 * @param null   $type
	 *
	 * @return string
	 */
	public function bindForColumn( string $name, $value = null, $type = null ): string
	{
		return $this->_bind( $this->name( $name, true ), $value, $type );
	}

	public function __clone()
	{
		$this->updatePrefix();
		foreach( array_keys( $this->parameters ) as $key )
		{
			if( $this->parameters[$key] instanceof Expression )
			{
				$this->parameters[$key] = clone $this->parameters[$key];
			}
		}
	}

	private function _bind( string $key, $value, $type ): string
	{
		$this->parameters[$key] = $value;
		if( $type !== null )
		{
			$this->types[$key] = $value;
		}
		return $key;
	}

	private function updatePrefix()
	{
		$this->paramPrefix = ":param" . ( self::$paramKey++ ) . "_";
	}
}