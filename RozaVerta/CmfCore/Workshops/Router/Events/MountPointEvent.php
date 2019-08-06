<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.04.2018
 * Time: 13:06
 */

namespace RozaVerta\CmfCore\Workshops\Router\Events;

use RozaVerta\CmfCore\Events\WorkshopEvent;

/**
 * Class MountPointEvent
 *
 * @package RozaVerta\CmfCore\Workshops\Router\Events
 */
abstract class MountPointEvent extends WorkshopEvent
{
	static public function eventName(): string
	{
		return "onSystemProcessorMount";
	}
}