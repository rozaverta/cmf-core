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
 * Class HandlerCreateEvent
 *
 * @property-read string $className
 *
 * @package RozaVerta\CmfCore\Workshops\Event\Events
 */
class HandlerCreateEvent extends AbstractLinkEvent
{
	/**
	 * HandlerCreateEvent constructor.
	 *
	 * @param HandlerProcessor $processor
	 * @param string $className
	 */
	public function __construct( HandlerProcessor $processor, string $className )
	{
		parent::__construct( $processor, "create", compact('className') );
	}
}