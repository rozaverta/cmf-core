<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

class Replace extends TextModifier
{
	protected function textFormat( string $value, array $attributes ): string
	{
		$all = count($attributes);
		if( $all < 1 )
		{
			return $value;
		}

		$search = (string) $attributes[0];
		$replace = $all > 1 ? (string) $attributes[1] : "";

		if($all > 2 && $attributes[2] === true)
		{
			return preg_replace($search, $replace, $value);
		}
		else
		{
			return str_replace($search, $replace, $value);
		}
	}
}