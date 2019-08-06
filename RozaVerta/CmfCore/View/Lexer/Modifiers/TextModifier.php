<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

abstract class TextModifier extends Modifier
{
	public function format( $value, array $attributes = [] )
	{
		if( self::checkFlag($attributes) && is_string($value) )
		{
			return $this->textFormat($value, $attributes);
		}
		else
		{
			return $value;
		}
	}

	abstract protected function textFormat(string $value, array $attributes): string;
}