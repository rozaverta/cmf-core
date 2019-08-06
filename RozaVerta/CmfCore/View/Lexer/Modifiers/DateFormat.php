<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\View\Interfaces\ModifierInterface;

class DateFormat implements ModifierInterface
{
	public function format( $value, array $attributes = [] )
	{
		$format = current($attributes);
		if( ! is_string($format) || ! strlen($format) )
		{
			return "";
		}

		if($value instanceof \DateTime)
		{
			return $value->format($format);
		}

		if(is_numeric($value))
		{
			$value = (int) $value;
		}
		else if( is_string($value) )
		{
			$value = strtotime($value);
			if( $value === false )
			{
				return "";
			}
		}

		return date($format, $value);
	}
}