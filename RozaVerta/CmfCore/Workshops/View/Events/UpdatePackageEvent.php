<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.08.2019
 * Time: 21:13
 */

namespace RozaVerta\CmfCore\Workshops\View\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;
use RozaVerta\CmfCore\Schemes\TemplatePackages_SchemeDesigner;
use RozaVerta\CmfCore\Support\Prop;

/**
 * Class UpdatePackageEvent
 *
 * @property-read Prop                            $manifest
 * @property-read TemplatePackages_SchemeDesigner $package
 * @property-read bool                            $force
 *
 * @package RozaVerta\CmfCore\Workshops\View\Events
 */
class UpdatePackageEvent extends PackageEvent
{
	public function __construct( WorkshopInterface $workshop, string $name, Prop $manifest, TemplatePackages_SchemeDesigner $package, bool $force = false )
	{
		parent::__construct( $workshop, "update", compact( "name", "manifest", "package", "force" ) );
	}
}