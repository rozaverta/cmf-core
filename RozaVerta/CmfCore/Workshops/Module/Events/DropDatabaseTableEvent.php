<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.04.2018
 * Time: 13:06
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;

class DropDatabaseTableEvent extends DatabaseTableEvent
{
	public function __construct( WorkshopInterface $workshop, array $data = [] )
	{
		parent::__construct( $workshop, "drop", $data );
	}
}