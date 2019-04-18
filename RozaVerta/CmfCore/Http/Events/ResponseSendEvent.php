<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace RozaVerta\CmfCore\Http\Events;

use RozaVerta\CmfCore\Event\Event;
use RozaVerta\CmfCore\Http\Response;

/**
 * Class ResponseSendEvent
 *
 * @property Response $response
 *
 * @package RozaVerta\CmfCore\Http\Events
 */
class ResponseSendEvent extends Event
{
	public function __construct( Response $response )
	{
		parent::__construct(compact('response'));
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	static public function eventName(): string
	{
		return "onResponseSend";
	}
}