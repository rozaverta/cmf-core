<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.08.2018
 * Time: 19:40
 */

namespace RozaVerta\CmfCore\Language\Events;

use RozaVerta\CmfCore\Language\LanguageManager;
use RozaVerta\CmfCore\Event\Event;

/**
 * Class ReadyLanguageEvent
 *
 * @property LanguageManager $instance
 * @property string $language
 *
 * @package RozaVerta\CmfCore\Language\Events
 */
abstract class LanguageEvent extends Event
{
	public function __construct( LanguageManager $instance, array $parameters = [] )
	{
		$parameters['instance'] = $instance;
		$parameters['language'] = $instance->getCurrent();
		parent::__construct($parameters);
	}

	public static function eventName(): string
	{
		return "onLanguage";
	}
}