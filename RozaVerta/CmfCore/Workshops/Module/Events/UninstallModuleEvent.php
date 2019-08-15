<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.08.2019
 * Time: 14:20
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Workshops\Module\ModuleComponent;

/**
 * Class UninstallModuleEvent
 *
 * @package RozaVerta\CmfCore\Workshops\Module\Events
 */
class UninstallModuleEvent extends ModuleEvent
{
	public function __construct( ModuleComponent $workshop )
	{
		parent::__construct( $workshop, "uninstall" );
	}
}