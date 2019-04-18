<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 17:38
 */

namespace RozaVerta\CmfCore\Workshops\Event\Events;

use RozaVerta\CmfCore\Workshops\Event\HandlerProcessor;

/**
 * Class LinkEvent
 *
 * @property-read string $className
 * @property-read string $eventName
 * @property-read int $priority
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
class LinkEvent extends AbstractLinkEvent
{
	/**
	 * LinkEvent constructor.
	 *
	 * @param HandlerProcessor $processor
	 * @param string $className
	 * @param string $eventName
	 * @param int $priority
	 */
	public function __construct( HandlerProcessor $processor, string $className, string $eventName, ?int $priority )
	{
		parent::__construct( $processor, "link", compact('className', 'eventName', 'priority') );
	}
}