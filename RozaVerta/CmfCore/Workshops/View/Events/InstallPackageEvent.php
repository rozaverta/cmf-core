<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.08.2019
 * Time: 16:21
 */

namespace RozaVerta\CmfCore\Workshops\View\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;
use RozaVerta\CmfCore\Support\Prop;

/**
 * Class InstallPackageEvent
 *
 * @property-read Prop $manifest
 *
 * @package RozaVerta\CmfCore\Workshops\View\Events
 */
class InstallPackageEvent extends PackageEvent
{
	public function __construct( WorkshopInterface $workshop, string $name, Prop $manifest )
	{
		parent::__construct( $workshop, "install", compact( "name", "manifest" ) );
	}
}