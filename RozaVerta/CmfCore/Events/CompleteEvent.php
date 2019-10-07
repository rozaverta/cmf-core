<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:45
 */

namespace RozaVerta\CmfCore\Events;

/**
 * Class CompleteEvent
 *
 * @property-read string $contentType
 * @property-read bool   $cache
 *
 * @package RozaVerta\CmfCore\Events
 */
class CompleteEvent extends SystemEvent
{
	public function __construct( string $contentType, bool $cache = false )
	{
		parent::__construct(compact('contentType', 'cache'));
	}
}