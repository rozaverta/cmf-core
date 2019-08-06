<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.04.2019
 * Time: 16:50
 */

namespace RozaVerta\CmfCore\Workshops\Router\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;

/**
 * Class MountEvent
 *
 * @property string $name
 * @property string $path
 * @property int $position
 *
 * @package RozaVerta\CmfCore\Workshops\Router\Events
 */
class MountEvent extends MountPointEvent
{
	public function __construct( WorkshopInterface $workshop, string $processName, array $params = [] )
	{
		parent::__construct( $workshop, $processName, $params );
		$this
			->setAllowed("name", "string")
			->setAllowed("path", "string")
			->setAllowed("position", "int");
	}
}