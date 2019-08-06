<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.05.2019
 * Time: 13:22
 */

namespace RozaVerta\CmfCore\Events;

use RozaVerta\CmfCore\Event\Event;
use RozaVerta\CmfCore\Route\Interfaces\ControllerInterface;

/**
 * Class ControllerCompleteEvent
 *
 * @property-read ControllerInterface $controller
 * @property array $pageData
 *
 * @package RozaVerta\CmfCore\Events
 */
class ControllerCompleteEvent extends Event
{
	/**
	 * ControllerCompleteEvent constructor.
	 *
	 * @param ControllerInterface $controller
	 * @param array $pageData
	 */
	public function __construct(ControllerInterface $controller, array $pageData)
	{
		parent::__construct(compact('controller', 'pageData'));
		$this->allowType('pageData', 'array');
	}

	/**
	 * Get event name.
	 *
	 * @return string
	 */
	static public function eventName(): string
	{
		return "onControllerComplete";
	}
}