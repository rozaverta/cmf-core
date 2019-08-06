<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.04.2019
 * Time: 16:50
 */

namespace RozaVerta\CmfCore\Workshops\Router\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;

/**
 * Class MountPointUpdateEvent
 *
 * @property-read int $id
 * @property-read string $oldName
 * @property-read string $oldPath
 * @property-read int $oldPosition
 * @property string $name
 * @property string $path
 * @property int $position
 *
 * @package RozaVerta\CmfCore\Workshops\Router\Events
 */
class MountPointUpdateEvent extends MountPointEvent
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