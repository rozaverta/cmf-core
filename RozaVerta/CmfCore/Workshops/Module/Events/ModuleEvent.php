<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.04.2018
 * Time: 13:06
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Events\WorkshopEvent;

/**
 * Class ModuleEvent
 *
 * @package RozaVerta\CmfCore\Workshops\Module\Events
 */
abstract class ModuleEvent extends WorkshopEvent
{
	static public function eventName(): string
	{
		return "onSystemProcessorModule";
	}
}