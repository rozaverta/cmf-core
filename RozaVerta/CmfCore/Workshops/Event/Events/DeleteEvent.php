<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 15:39
 */

namespace RozaVerta\CmfCore\Workshops\Event\Events;

use RozaVerta\CmfCore\Workshops\Event\EventProcessor;

/**
 * Class DeleteEvent
 *
 * @property-read string $eventName
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
class DeleteEvent extends AbstractEvent
{
	public function __construct( EventProcessor $workshop, string $eventName )
	{
		parent::__construct( $workshop, "delete", compact('eventName') );
	}
}