<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 13:21
 */

namespace RozaVerta\CmfCore\Helper;

use Closure;

final class Callback
{
	private function __construct()
	{
	}

	/**
	 * Apply callback with plain arguments and return result
	 *
	 * @param Closure $callback
	 * @param array ...$args
	 * @return mixed
	 */
	public static function tap( Closure $callback, ... $args )
	{
		return $callback( ... $args );
	}

	/**
	 * Apply callback with arguments and return result
	 *
	 * @param Closure $callback
	 * @param array $args
	 * @return mixed
	 */
	public static function tapArray( Closure $callback, array $args = [] )
	{
		return $callback( ... $args );
	}
}