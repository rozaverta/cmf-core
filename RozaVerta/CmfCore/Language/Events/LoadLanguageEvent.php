<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.08.2018
 * Time: 19:53
 */

namespace RozaVerta\CmfCore\Language\Events;

use RozaVerta\CmfCore\Language\LanguageManager;

/**
 * Class LoadLanguageEvent
 *
 * @property $package
 *
 * @package RozaVerta\CmfCore\Language\Events
 */
class LoadLanguageEvent extends LanguageEvent
{
	public function __construct( LanguageManager $instance, $package )
	{
		parent::__construct($instance, compact('package'));
	}
}