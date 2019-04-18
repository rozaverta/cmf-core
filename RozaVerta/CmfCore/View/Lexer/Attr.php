<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.03.2019
 * Time: 21:38
 */

namespace RozaVerta\CmfCore\View\Lexer;

class Attr
{
	const T_SCALAR = 1;
	const T_ARRAY  = 2;
	const T_NODE   = 3;

	public $type  = self::T_SCALAR;
	public $name  = '';
	public $value = '';
	public $quote = '';

	static public function toText(Attr $at): string
	{
		$pref = $at->name;

		if($at->type === Attr::T_SCALAR)
		{
			if($at->value === true)
			{
				return $pref;
			}

			if($at->value === false)
			{
				return $pref . '=false';
			}

			if(is_null($at->value))
			{
				return $pref . '=null';
			}

			$val = (string) $at->value;
			if($at->quote)
			{
				$val = "'" . str_replace("'", "''", $val) . "'";
			}

			return $pref . '=' . $val;
		}

		if($at->type === Attr::T_ARRAY)
		{
			$map = [];

			foreach((array) $at->value as $item)
			{
				if(is_string($item))
				{
					$map[] = "'" . str_replace("'", "''", $item) . "'";
				}
				else if(is_bool($item))
				{
					$map[] = $item ? 'true' : 'false';
				}
				else if(is_null($item))
				{
					$map[] = 'null';
				}
				else if(is_scalar($item))
				{
					$map[] = $item;
				}
				else if($item instanceof Node)
				{
					$map[] = Node::toText($item, 'format');
				}
			}

			if( !count($map) )
			{
				return $pref . '=[]';
			}

			return $pref . '=[ ' . implode(', ', $map) . ' ]';
		}

		$val = $at->value;
		if($at->type === Attr::T_NODE && $val instanceof Node)
		{
			$val = Node::toText($val, 'format');
		}

		return $pref . '=' . $val;
	}
}