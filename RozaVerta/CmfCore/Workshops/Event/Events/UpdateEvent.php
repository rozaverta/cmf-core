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
 * Class UpdateEvent
 *
 * @property-read string $eventName
 * @property-read string $eventTitle
 * @property-read bool $isCompletable
 * @property-read array $willChanged
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
class UpdateEvent extends AbstractEvent
{
	public function __construct( EventProcessor $workshop, string $eventName, string $eventTitle, bool $isCompletable, array $willChanged = [] )
	{
		parent::__construct( $workshop, "update", compact('eventName', 'eventTitle', 'isCompletable', 'willChanged') );
	}
}