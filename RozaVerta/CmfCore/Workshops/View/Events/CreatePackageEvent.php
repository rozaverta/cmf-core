<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.08.2019
 * Time: 14:09
 */

namespace RozaVerta\CmfCore\Workshops\View\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;

/**
 * Class CreatePackageEvent
 *
 * @package RozaVerta\CmfCore\Workshops\View\Events
 */
class CreatePackageEvent extends PackageEvent
{
	public function __construct( WorkshopInterface $workshop, string $name )
	{
		parent::__construct( $workshop, "create", compact( "name" ) );
	}
}