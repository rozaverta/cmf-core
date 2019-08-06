<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\Helper\Str;

class Length extends Modifier
{
	public function format( $value, array $attributes = [] )
	{
		if( ! self::checkFlag($attributes) )
		{
			return $value;
		}

		if( is_string($value) )
		{
			return Str::len($value);
		}
		else if( is_array($value) || $value instanceof \Countable )
		{
			return count($value);
		}
		else
		{
			return 0;
		}
	}
}