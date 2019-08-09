<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.08.2019
 * Time: 11:32
 */

namespace RozaVerta\CmfCore\Workshops\View\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;
use RozaVerta\CmfCore\Schemes\TemplatePackages_SchemeDesigner;
use RozaVerta\CmfCore\Support\Prop;

/**
 * Class UninstallPackageEvent
 *
 * @property-read Prop                            $manifest
 * @property-read TemplatePackages_SchemeDesigner $package
 *
 * @package RozaVerta\CmfCore\Workshops\View\Events
 */
class UninstallPackageEvent extends PackageEvent
{
	public function __construct( WorkshopInterface $workshop, string $name, Prop $manifest, TemplatePackages_SchemeDesigner $package )
	{
		parent::__construct( $workshop, "uninstall", compact( "name", "manifest", "package" ) );
	}
}