<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.08.2018
 * Time: 12:18
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Events\WorkshopEvent;
use RozaVerta\CmfCore\Interfaces\WorkshopInterface;

/**
 * Class SaveConfigFileEvent
 *
 * @package RozaVerta\CmfCore\Workshops\Module\Events
 */
class SaveConfigFileEvent extends WorkshopEvent
{
	public function __construct( WorkshopInterface $workshop )
	{
		parent::__construct( $workshop, "save" );
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	static public function eventName(): string
	{
		return "onSystemProcessorConfigFile";
	}
}