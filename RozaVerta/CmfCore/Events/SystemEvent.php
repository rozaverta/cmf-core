<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.03.2019
 * Time: 16:18
 */

namespace RozaVerta\CmfCore\Events;

use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Event\Event;

/**
 * Abstract class SystemEvent
 *
 * @property \RozaVerta\CmfCore\App $app
 *
 * @package RozaVerta\CmfCore\Events
 */
abstract class SystemEvent extends Event
{
	public function __construct(array $params = [])
	{
		$params["app"] = App::getInstance();
		parent::__construct($params);
	}

	static public function eventName(): string
	{
		return "onSystem";
	}
}