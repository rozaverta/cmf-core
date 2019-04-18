<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\Helper\Str;

class Lower extends TextModifier
{
	protected function textFormat( string $value, array $attributes ): string
	{
		return Str::lower( $value );
	}
}