<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.08.2018
 * Time: 11:56
 */

namespace RozaVerta\CmfCore\View\Events;

use RozaVerta\CmfCore\Event\Event;
use RozaVerta\CmfCore\View\View;

/**
 * Class RenderEvent
 *
 * @property-read View $view
 *
 * @package RozaVerta\CmfCore\View\Events
 */
abstract class RenderEvent extends Event
{
	public function __construct( View $view, array $params = [] )
	{
		$params["view"] = $view;
		parent::__construct( $params );
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	static public function eventName(): string
	{
		return "onRender";
	}
}