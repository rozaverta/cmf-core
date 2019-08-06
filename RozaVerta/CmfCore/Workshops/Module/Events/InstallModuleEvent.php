<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.04.2018
 * Time: 2:45
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Workshops\Module\ModuleComponent;

/**
 * Class InstallModuleEvent
 *
 * @package RozaVerta\CmfCore\Workshops\Module\Events
 */
class InstallModuleEvent extends ModuleEvent
{
	public function __construct( ModuleComponent $workshop )
	{
		parent::__construct( $workshop, "install" );
	}
}