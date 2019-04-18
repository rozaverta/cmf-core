<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.03.2019
 * Time: 23:48
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;
use RozaVerta\CmfCore\Module\ModuleManifest;

/**
 * Class RegisterModuleEvent
 *
 * @package RozaVerta\CmfCore\Workshops\Module\Events
 */
class RegisterModuleEvent extends ModuleEvent
{
	/**
	 * RegisterModuleEvent constructor.
	 *
	 * @param WorkshopInterface $workshop
	 * @param string $moduleName
	 * @param string $namespaceName
	 * @param ModuleManifest $config
	 */
	public function __construct( WorkshopInterface $workshop, string $moduleName, string $namespaceName, ModuleManifest $config )
	{
		parent::__construct( $workshop, "register", compact('moduleName', 'namespaceName', 'config') );
	}
}