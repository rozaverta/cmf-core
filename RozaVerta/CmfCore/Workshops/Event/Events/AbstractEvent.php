<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 15:39
 */

namespace RozaVerta\CmfCore\Workshops\Event\Events;

use RozaVerta\CmfCore\Events\WorkshopEvent;

/**
 * Class AbstractEvent
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
abstract class AbstractEvent extends WorkshopEvent
{
	public static function eventName(): string
	{
		return "onSystemProcessorEvent";
	}
}