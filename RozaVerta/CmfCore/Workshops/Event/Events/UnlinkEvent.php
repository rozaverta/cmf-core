<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 17:39
 */

namespace RozaVerta\CmfCore\Workshops\Event\Events;

use RozaVerta\CmfCore\Workshops\Event\HandlerProcessor;

/**
 * Class UnlinkEvent
 *
 * @property-read int $linkId
 * @property-read string $className
 * @property-read string $eventName
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
class UnlinkEvent extends AbstractLinkEvent
{
	/**
	 * UnlinkEvent constructor.
	 *
	 * @param HandlerProcessor $processor
	 * @param int $linkId
	 * @param string $className
	 * @param string $eventName
	 */
	public function __construct( HandlerProcessor $processor, int $linkId, string $className, string $eventName )
	{
		parent::__construct( $processor, "unlink", compact('linkId','className', 'eventName') );
	}
}