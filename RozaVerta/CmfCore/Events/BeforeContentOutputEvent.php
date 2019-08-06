<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace RozaVerta\CmfCore\Events;

/**
 * Class BeforeContentOutputEvent
 *
 * @property string $contentType
 * @property bool $cache
 * @property bool $cacheable
 *
 * @package RozaVerta\CmfCore\Events
 */
class BeforeContentOutputEvent extends SystemEvent
{
	public function __construct( string $contentType, bool $cache = false, bool $cacheable = false )
	{
		parent::__construct([
			'contentType' => $contentType,
			'cache' => $cache,
			'cacheable' => ! $cache && $cacheable
		]);
		$this->setAllowed("cacheable", "bool");
	}
}