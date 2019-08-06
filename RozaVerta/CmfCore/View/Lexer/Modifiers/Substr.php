<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\Helper\Str;

class Substr extends TextModifier
{
	protected function textFormat( string $value, array $attributes ): string
	{
		$all = count($attributes);
		if( $all < 1 || ! is_numeric($attributes[0]))
		{
			return $value;
		}

		$start = (int) $attributes[0];

		if( $all < 2 || ! is_numeric($attributes[1]) )
		{
			return Str::cut($value, $start);
		}
		else
		{
			return Str::cut($value, $start, (int) $attributes[1]);
		}
	}
}