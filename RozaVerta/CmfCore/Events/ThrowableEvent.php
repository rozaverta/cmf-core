<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.03.2019
 * Time: 22:29
 */

namespace RozaVerta\CmfCore\Events;

use Error;
use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Event\Event;
use Throwable;

/**
 * Class ThrowableEvent
 *
 * @property \Exception | Error $throwable
 * @property \RozaVerta\CmfCore\App $app
 * @property boolean $error
 *
 * @package RozaVerta\CmfCore\Events
 */
final class ThrowableEvent extends Event
{
	public function __construct( Throwable $throwable )
	{
		parent::__construct([
			"app" => App::getInstance(),
			"throwable" => $throwable,
			"error" => $throwable instanceof Error
		]);
	}

	/**
	 * Get event name
	 *
	 * @return string
	 */
	static public function eventName(): string
	{
		return "onThrowable";
	}
}