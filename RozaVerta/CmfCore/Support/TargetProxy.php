<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.09.2017
 * Time: 23:55
 */

namespace RozaVerta\CmfCore\Support;

class TargetProxy
{
	/**
	 * The target being tapped.
	 *
	 * @var mixed
	 */
	public $target;

	/**
	 * Create a new tap proxy instance.
	 *
	 * @param  mixed  $target
	 */
	public function __construct($target)
	{
		$this->target = $target;
	}

	/**
	 * Dynamically pass method calls to the target.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		return $this->target->{$method}(...$parameters);
	}
}