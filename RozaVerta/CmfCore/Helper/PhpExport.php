<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.01.2015
 * Time: 19:57
 */

namespace RozaVerta\CmfCore\Helper;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;

/**
 * Class PhpExport
 *
 * @method static PhpExport getInstance()
 *
 * @package RozaVerta\CmfCore\Helper
 */
class PhpExport
{
	use SingletonInstanceTrait;

	const ARRAY_PRETTY_PRINT = 1;
	const SHORT_ARRAY_SYNTAX = 2;

	private $pretty = false;
	private $arrayOpen = '[';
	private $arrayClose = ']';
	private $maxDepth = 10;
	private $depth = [];

	/**
	 * Create data string `$data = 'value';`
	 *
	 * @param mixed $value
	 * @param string $name
	 * @param bool $init
	 * @param bool $smart_depth
	 * @return string
	 */
	public function data( $value, $name = "data", $init = true, $smart_depth = false )
	{
		$is_object = is_object( $value );
		$get = '';
		$map = '$' . $name;

		if( $is_object )
		{
			if( $this->fromExportObject($value, $result) )
			{
				return $map . ' = ' . $result . ";\n";
			}
		}
		else if( ! is_array( $value ) )
		{
			$get = $init ? ( $map . ' = ' . $this->fromType( $value, 0 ) . ";" ) : "";
			$this->clean();
			return $get;
		}

		$this->depth =
			[
				0 => & $value
			];

		if( $init )
		{
			$get .= $map;
			$get .= $is_object ? " = new \\ArrayIterator();\n" : " = {$this->arrayOpen}{$this->arrayClose};\n";
		}

		if( $is_object && !is_iterable( $value ) )
		{
			$value = get_object_vars( $value );
		}

		$j = 0;
		$short = $smart_depth && count($value) < 6;

		foreach( $value as $key => $val )
		{
			$get .= $map;
			if( $key === $j )
			{
				$key = $j ++;
				$get .= "[]";
			}
			else
			{
				$key = $this->string( $key );
				$get .= '[' . $key . ']';
			}

			if( $short && is_array($val) && count($val) > 9 )
			{
				$get .= " = {$this->arrayOpen}{$this->arrayClose};\n";
				$key = $map . '[' . $key . ']';
				$j2 = 0;

				foreach( $val as $key2 => $val2 )
				{
					$get .= $key;
					if( $key2 === $j2 )
					{
						++ $j2;
						$get .= "[] = ";
					}
					else
					{
						$get .= '[' . $this->string( $key2 ) . '] = ';
					}
					$get .= $this->fromType( $val2, 2, 0 ) . ";\n";
				}
			}
			else
			{
				$get .= ' = ' . $this->fromType( $val, 1, 0 ) . ";\n";
			}
		}

		$this->clean();
		return $get;
	}

	public function php( $val )
	{
		$get = $this->fromType( $val, 0 );
		$this->clean();
		return $get;
	}

	/**
	 * @param array | object $values
	 * @return string
	 */
	public function arrayList( $values )
	{
		$is_object = false;

		if( is_object($values) )
		{
			$is_object = true;
		}
		else if( !is_array($values) )
		{
			$get = $this->arrayOpen . $this->arrayClose;
			$this->clean();
			return $get;
		}

		$this->depth =
			[
				0 => & $values
			];

		if( $is_object && !is_iterable( $values ) )
		{
			$values = get_object_vars( $values );
		}

		$values = (array) $values;
		$get = $this->arrayOpen;
		$first = true;

		foreach( $values as $val )
		{
			if( $first )
			{
				$first = false;
			}
			else
			{
				$get .= ",";
			}

			$get .= $this->fromType( $val, 1 );
		}

		$get .= $this->arrayClose;
		$this->clean();
		return $get;
	}

	/**
	 * @param array | object $values
	 * @return string
	 */
	public function assoc( $values )
	{
		$is_object = false;

		if( is_object($values) )
		{
			$is_object = true;
		}
		else if( !is_array($values) )
		{
			$get = $this->arrayOpen . $this->arrayClose;
			$this->clean();
			return $get;
		}

		if( $is_object && !is_iterable( $values ) )
		{
			$values = get_object_vars( $values );
		}

		$this->depth =
			[
				0 => & $values
			];

		$get = $this->arrayOpen;
		$first = true;
		$j = 0;

		foreach( $values as $key => $value )
		{
			if( $first )
			{
				$first = false;
			}
			else
			{
				$get .= ", ";
			}

			if( $j === $key )
			{
				++ $j;
			}
			else
			{
				$get .= $this->string( $key ) . ' => ';
			}

			$get .= $this->fromType( $value, 1 );
		}

		$get .= $this->arrayClose;
		$this->clean();
		return $get;
	}

	public function string( $str )
	{
		if( is_string($str) || is_int($str) || is_double($str) )
		{
			return var_export((string) $str, true);
		}

		else if( is_object($str) && method_exists($str, "__toString") )
		{
			return var_export((string) $str, true);
		}

		else {
			$get = '""';
		}

		$this->clean();
		return $get;
	}

	public function config( $flag, $depth = 10 )
	{
		$flag = (int) $flag;
		$depth = (int) $depth;

		// display format
		if( $flag & self::SHORT_ARRAY_SYNTAX )
		{
			$this->arrayOpen = '[';
			$this->arrayClose = ']';
		}
		else
		{
			$this->arrayOpen = 'array(';
			$this->arrayClose = ')';
		}

		// pretty print
		if( $flag & self::ARRAY_PRETTY_PRINT )
		{
			$this->pretty = true;
		}
		else
		{
			$this->pretty = false;
		}

		// max depth level
		if( $depth < 1 )
		{
			$this->maxDepth = 0;
		}
		else if( $depth > 256 )
		{
			$this->maxDepth = 256;
		}
		else {
			$this->maxDepth = $depth;
		}

		return $this;
	}

	public function escape( $value )
	{
		do {
			$tmp = '{php_close' . Str::random(20) . '/}';
		}
		while( strpos($value, $tmp) !== false );

		$value = str_replace('<?', '<?= "<"; ' . $tmp . '?', $value);
		$value = str_replace('?>', '?<?= ">"; ?>', $value);
		$value = str_replace($tmp, '?>', $value);

		return $value;
	}

	// private

	private function getAssoc( $values, $level, $printLevel )
	{
		$tab = $this->pretty ? str_repeat("\t", $printLevel) : "";
		$out = $this->arrayOpen;
		$first = true;
		$isArray = is_array( $values ) && !Arr::associative( $values );

		foreach( $values as $key => $value )
		{
			if( $first )
			{
				$first = false;
			}
			else
			{
				$out .= ", ";
			}

			if( $this->pretty )
			{
				$out .= "\n\t" . $tab;
			}

			if( !$isArray )
			{
				$out .= (is_int($key) ? $key : $this->string( $key )) . ' => ';
			}

			$out .= $this->fromType( $value, $level + 1, $printLevel + 1 );
		}

		if( $this->pretty && !$first )
		{
			$out .= "\n" . $tab;
		}

		return $out . $this->arrayClose;
	}

	private function isDepth( & $val, $level )
	{
		if( $level < 1 )
		{
			return false;
		}

		for( $i = 0; $i < $level; $i++ )
		{
			if( $this->depth[$i] === $val )
			{
				return true;
			}
		}

		return false;
	}

	private function clean()
	{
		$this->pretty = false;
		$this->arrayOpen = '[';
		$this->arrayClose = ']';
		$this->maxDepth = 10;
		$this->depth = [];
	}

	private function fromType( & $val, $level = 0, $printLevel = 0 )
	{
		$type = gettype( $val );
		$is_object = $type == "object";

		if( $is_object )
		{
			if( $this->fromExportObject($val, $result) )
			{
				return $result;
			}

			// fix recursive
			if( $this->isDepth($val, $level) )
			{
				return $this->arrayOpen . $this->arrayClose;
			}
			else
			{
				$this->depth[$level] = & $val;
			}

			$type = "array";
			if( !is_iterable( $val ) )
			{
				$val = get_object_vars( $val );
			}
		}

		switch( $type )
		{
			case "array" :

				if( !$is_object )
				{
					if( $this->isDepth($val, $level) )
					{
						return $this->arrayOpen . $this->arrayClose;
					}
					else
					{
						$this->depth[$level] = & $val;
					}
				}

				return $level >= $this->maxDepth ? ( $this->arrayOpen . $this->arrayClose ) : $this->getAssoc( $val, $level, $printLevel );

			case "boolean"  : return $val ? "true" : "false";
			case "resource" :
			case "NULL"     : return "NULL";
			case "double"   :
			case "integer"  : return $val;
		}

		return $this->string( $val );
	}

	private function fromExportObject($val, & $result)
	{
		// 1. The object custom VarExportInterface
		if( $val instanceof VarExportInterface )
		{
			if( $val instanceof PhpVarExportProxy )
			{
				$result = var_export( $val, true );
			}
			else
			{
				$result = get_class( $val ) . '::__set_state(' . var_export( PhpVarExportProxy::proxy( $val->getArrayForVarExport(), $this->maxDepth ), true ) . ')';
			}
		}

		// 2. The object itself converts data
		else if( method_exists($val, '__set_state') )
		{
			$result = var_export($val, true);
		}

		else
		{
			return false;
		}

		return true;
	}
}