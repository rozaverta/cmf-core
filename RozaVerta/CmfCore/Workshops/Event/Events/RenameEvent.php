<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 15:39
 */

namespace RozaVerta\CmfCore\Workshops\Event\Events;

use RozaVerta\CmfCore\Workshops\Event\EventProcessor;

/**
 * Class RenameEvent
 *
 * @property-read string $oldEventName
 * @property-read string $newEventName
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
class RenameEvent extends AbstractEvent
{
	public function __construct( EventProcessor $workshop, string $oldEventName, string $newEventName )
	{
		parent::__construct( $workshop, "rename", compact('oldEventName', 'newEventName') );
	}
}