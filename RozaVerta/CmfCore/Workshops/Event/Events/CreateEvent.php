<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 15:39
 */

namespace RozaVerta\CmfCore\Workshops\Event\Events;

use RozaVerta\CmfCore\Workshops\Event\EventProcessor;

/**
 * Class CreateEvent
 *
 * @property-read string $eventName
 * @property-read string $eventTitle
 * @property-read bool $isCompletable
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
class CreateEvent extends AbstractEvent
{
	public function __construct( EventProcessor $workshop, string $eventName, string $eventTitle, bool $isCompletable )
	{
		parent::__construct( $workshop, "create", compact('eventName', 'eventTitle', 'isCompletable') );
	}
}