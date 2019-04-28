<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 28.04.2019
 * Time: 13:45
 */

namespace RozaVerta\CmfCore\Workshops\Helper;

class Tool
{
	/**
	 * Compares values
	 *
	 * @param $a
	 * @param $b
	 * @param int $maxDepth
	 * @return bool
	 */
	static public function compareProperties($a, $b, int $maxDepth = 512): bool
	{
		if( $maxDepth < 0 )
		{
			return false;
		}

		if( is_object($a))
		{
			$a = get_object_vars($a);
		}

		if( is_object($b) )
		{
			$b = get_object_vars($b);
		}

		if( is_array($a) )
		{
			if( !is_array($b) || count($a) !== count($b) )
			{
				return false;
			}

			foreach($a as $key => $value)
			{
				if( !array_key_exists($key, $b) || ! self::compareProperties($value, $b[$key], $maxDepth - 1) )
				{
					return false;
				}
			}

			return true;
		}

		return $a === $b;
	}
}