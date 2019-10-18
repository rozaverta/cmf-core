<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace RozaVerta\CmfCore\Events;

use RozaVerta\CmfCore\Route\Interfaces\ControllerInterface;

/**
 * Class ChangeControllerEvent
 *
 * @property ControllerInterface $controller
 *
 * @package RozaVerta\CmfCore\Events
 */
class ChangeControllerEvent extends SystemEvent
{
	public function __construct( ControllerInterface $controller )
	{
		parent::__construct( compact( 'controller' ) );
		$this->setAllowed( "controller", ControllerInterface::class );
	}
}