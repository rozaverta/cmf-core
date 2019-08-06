<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 21:28
 */

namespace RozaVerta\CmfCore\Http\Events;

use RozaVerta\CmfCore\Event\Event;
use RozaVerta\CmfCore\Http\Response;

/**
 * Class ResponseRedirectEvent
 *
 *
 * @property Response response
 * @property string location
 * @property boolean permanent
 * @property boolean refresh
 *
 * @package RozaVerta\CmfCore\Http\Events
 */
class ResponseRedirectEvent extends Event
{
	public function __construct( Response $response, string $location, bool $permanent, bool $refresh )
	{
		parent::__construct(compact('response', 'location', 'permanent', 'refresh'));
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	static public function eventName(): string
	{
		return "onResponseRedirect";
	}
}