<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

class StripTags extends TextModifier
{
	protected function textFormat( string $value, array $attributes ): string
	{
		$allowed = null;

		$all = count($attributes);
		if($all === 1)
		{
			$allowed = current($attributes);
			if(strpos($allowed, '<') === false)
			{
				$allowed = '<' . str_replace(',', '><', str_replace(' ', '', $allowed)) . '>';
			}
		}

		else if($all > 1)
		{
			if(strpos(current($attributes), '<') !== false)
			{
				$allowed = implode("", $attributes);
			}
			else
			{
				$allowed = '<' . implode("><", $attributes) . '>';
			}
		}

		return strip_tags( $value, $allowed );
	}
}