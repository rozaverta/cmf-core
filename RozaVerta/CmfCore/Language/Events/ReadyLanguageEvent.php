<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace RozaVerta\CmfCore\Language\Events;

use RozaVerta\CmfCore\Language\LanguageManager;

/**
 * Class ReadyLanguageEvent
 *
 * @package RozaVerta\CmfCore\Language\Events
 */
class ReadyLanguageEvent extends LanguageEvent
{
	public function __construct( LanguageManager $instance )
	{
		parent::__construct($instance);
		$this->setAllowed("language", static function( $language ) {
			return is_string($language) && LanguageManager::valid($language);
		});
	}
}