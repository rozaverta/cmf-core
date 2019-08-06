<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.04.2018
 * Time: 13:06
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Events\WorkshopEvent;

/**
 * Class DatabaseTableEvent
 *
 * @property-read string $tableName
 *
 * @package RozaVerta\CmfCore\Workshops\Module\Events
 */
abstract class DatabaseTableEvent extends WorkshopEvent
{
	static public function eventName(): string
	{
		return "onSystemProcessorDatabaseTable";
	}
}