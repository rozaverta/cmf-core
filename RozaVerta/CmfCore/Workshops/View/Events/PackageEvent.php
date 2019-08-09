<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.08.2019
 * Time: 16:22
 */

namespace RozaVerta\CmfCore\Workshops\View\Events;

use RozaVerta\CmfCore\Events\WorkshopEvent;

/**
 * Class PackageEvent
 *
 * @property-read string $name
 *
 * @package RozaVerta\CmfCore\Workshops\View\Events
 */
abstract class PackageEvent extends WorkshopEvent
{
	static public function eventName(): string
	{
		return "onSystemProcessorPackage";
	}
}