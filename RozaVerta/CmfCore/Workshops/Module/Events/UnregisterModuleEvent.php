<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 2:42
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;

class UnregisterModuleEvent extends ModuleEvent
{
	/**
	 * UnregisterModuleEvent constructor.
	 *
	 * @param WorkshopInterface $workshop
	 */
	public function __construct( WorkshopInterface $workshop )
	{
		parent::__construct( $workshop, "unregister" );
	}
}