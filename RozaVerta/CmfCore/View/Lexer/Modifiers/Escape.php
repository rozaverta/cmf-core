<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\Helper\Str;

class Escape extends TextModifier
{
	protected function textFormat( string $value, array $attributes ): string
	{
		return htmlspecialchars( $value, $this->getFlags($attributes), Str::encoding() );
	}

	protected function getFlags(array $attributes)
	{
		if(count($attributes))
		{
			$flags = 0;
			foreach($attributes as $flag)
			{
				$flag = strtoupper($flag);
				switch($flag)
				{
					case "COMPAT": $flags = $flags | ENT_COMPAT; break;
					case "QUOTES": $flags = $flags | ENT_QUOTES; break;
					case "NOQUOTES": $flags = $flags | ENT_NOQUOTES; break;
					case "IGNORE": $flags = $flags | ENT_IGNORE; break;
					case "SUBSTITUTE": $flags = $flags | ENT_SUBSTITUTE; break;
					case "DISALLOWED": $flags = $flags | ENT_DISALLOWED; break;
					case "HTML401": $flags = $flags | ENT_HTML401; break;
					case "XML1": $flags = $flags | ENT_XML1; break;
					case "XHTML": $flags = $flags | ENT_XHTML; break;
					case "HTML5": $flags = $flags | ENT_HTML5; break;
				}
			}
		}
		else
		{
			$flags = ENT_COMPAT | ENT_HTML401;
		}

		return $flags;
	}
}