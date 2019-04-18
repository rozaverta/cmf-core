<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 13:51
 */

namespace RozaVerta\CmfCore\Helper;

use RozaVerta\CmfCore\Support\Collection;

final class Data
{
	private function __construct()
	{
	}

	/**
	 * Return the default value of the given value
	 *
	 * @param  mixed  $value
	 * @return mixed
	 */
	public static function value($value)
	{
		return $value instanceof \Closure ? $value() : $value;
	}

	/**
	 * Get an item from an array or object using "dot" notation.
	 *
	 * @param  mixed   $target
	 * @param  string|array  $key
	 * @param  mixed   $default
	 * @return mixed
	 */
	public static function getIn($target, $key, $default = null)
	{
		if( is_null($key) )
		{
			return $target;
		}

		if( ! is_array($key) )
		{
			$key = explode(".", $key);
		}

		while (! is_null($segment = array_shift($key)))
		{
			if ($segment === '*')
			{
				if($target instanceof Collection)
				{
					$target = $target->getAll();
				}
				else if(! is_array($target))
				{
					return self::value($default);
				}

				$result = Arr::pluck($target, $key);
				return in_array('*', $key) ? Arr::collapse($result) : $result;
			}

			if(Arr::accessible($target) && Arr::exists($target, $segment))
			{
				$target = $target[$segment];
			}
			else if(is_object($target) && isset($target->{$segment}))
			{
				$target = $target->{$segment};
			}
			else
			{
				return self::value($default);
			}
		}

		return $target;
	}
}