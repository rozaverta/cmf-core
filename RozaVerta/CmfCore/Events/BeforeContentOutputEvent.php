<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
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
	public function __construct( string $content_type, bool $cache = false, bool $cacheable = false )
	{
		parent::__construct([
			'contentType' => $content_type,
			'cache' => $cache,
			'cacheable' => ! $cache && $cacheable
		]);
		$this->setAllowed("cacheable", "bool");
	}
}