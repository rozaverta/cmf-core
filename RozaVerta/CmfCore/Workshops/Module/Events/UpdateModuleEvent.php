<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.03.2019
 * Time: 11:30
 */

namespace RozaVerta\CmfCore\Workshops\Module\Events;

use RozaVerta\CmfCore\Workshops\Module\ModuleComponent;

/**
 * Class UpdateModuleEvent
 *
 * @property-read bool $force
 * @property-read string $oldVersion
 *
 * @package RozaVerta\CmfCore\Workshops\Module\Events
 */
class UpdateModuleEvent extends ModuleEvent
{
	/**
	 * UpdateModuleEvent constructor.
	 * @param ModuleComponent $workshop
	 * @param bool $force
	 * @param string $oldVersion
	 */
	public function __construct( ModuleComponent $workshop, bool $force, $oldVersion )
	{
		parent::__construct( $workshop, "update", compact('force', 'oldVersion') );
	}
}