<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:55
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\View\Interfaces\ModifierInterface;

class Nil implements ModifierInterface
{
	public function format( $value, array $attributes = [] )
	{
		return $value;
	}
}