<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 17:39
 */

namespace RozaVerta\CmfCore\Workshops\Event\Events;

use RozaVerta\CmfCore\Events\WorkshopEvent;

/**
 * Class AbstractHandlerEvent
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
abstract class AbstractHandlerEvent extends WorkshopEvent
{
	public static function eventName(): string
	{
		return "onSystemProcessorEventHandler";
	}
}