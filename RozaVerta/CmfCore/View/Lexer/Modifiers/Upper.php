<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\Helper\Str;

class Upper extends TextModifier
{
	protected function textFormat( string $value, array $attributes ): string
	{
		return Str::upper( $value );
	}
}