<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace RozaVerta\CmfCore\View\Events;

use RozaVerta\CmfCore\View\View;

/**
 * Class RenderGetterEvent
 *
 * @property-read  string $propertyName
 * @property mixed $propertyValue
 *
 * @package RozaVerta\CmfCore\View\Events
 */
class RenderGetterEvent extends RenderEvent
{
	public function __construct( View $view, string $propertyName, $propertyValue )
	{
		parent::__construct($view, compact('propertyName', 'propertyValue'));
		$this->setAllowed("propertyValue");
	}
}