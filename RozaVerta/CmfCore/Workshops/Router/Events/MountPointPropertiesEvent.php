<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.04.2019
 * Time: 16:50
 */

namespace RozaVerta\CmfCore\Workshops\Router\Events;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;

/**
 * Class MountPointPropertiesEvent
 *
 * @property-read int $id
 * @property-read array $oldProperties
 * @property array $properties
 *
 * @package RozaVerta\CmfCore\Workshops\Router\Events
 */
class MountPointPropertiesEvent extends MountPointEvent
{
	public function __construct( WorkshopInterface $workshop, string $processName, array $params = [] )
	{
		parent::__construct( $workshop, $processName, $params);
		$this->setAllowed("properties", "array");
	}
}