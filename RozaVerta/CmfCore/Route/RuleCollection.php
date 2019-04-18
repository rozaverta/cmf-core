<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2018
 * Time: 15:00
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Interfaces\TypeOfInterface;
use RozaVerta\CmfCore\Route\Interfaces\RuleInterface;
use RozaVerta\CmfCore\Support\Collection;

class RuleCollection extends Collection implements TypeOfInterface
{
	public function typeOf( & $value, $name = null ): bool
	{
		return is_null($name) && $value instanceof RuleInterface;
	}
}