<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 30.08.2018
 * Time: 3:27
 */

namespace RozaVerta\CmfCore\Log;

use RozaVerta\CmfCore\Interfaces\ThrowableInterface;
use RozaVerta\CmfCore\Support\Text;
use Throwable;

/**
 * Class Logger
 *
 * @package RozaVerta\CmfCore\Log
 */
final class Logger
{
	/**
	 * Detailed debug information
	 */
	const DEBUG = 10;

	/**
	 * Interesting events
	 */
	const INFO = 20;

	/**
	 * Uncommon events
	 */
	const NOTICE = 30;

	/**
	 * Exceptional occurrences that are not errors
	 */
	const WARNING = 40;

	/**
	 * Runtime errors
	 */
	const ERROR = 50;

	/**
	 * Critical conditions
	 */
	const CRITICAL = 60;

	/**
	 * Action must be taken immediately
	 */
	const ALERT = 70;

	/**
	 * Urgent alert.
	 */
	const EMERGENCY = 80;

	/**
	 * This is a static variable and not a constant to serve as an extension point for custom levels
	 *
	 * @var string[] $levels Logging levels with the levels as key
	 */
	protected static $levels = [
		self::DEBUG     => 'DEBUG',
		self::INFO      => 'INFO',
		self::NOTICE    => 'NOTICE',
		self::WARNING   => 'WARNING',
		self::ERROR     => 'ERROR',
		self::CRITICAL  => 'CRITICAL',
		self::ALERT     => 'ALERT',
		self::EMERGENCY => 'EMERGENCY',
	];

	/**
	 * Convert int level to string
	 *
	 * @param int $level
	 * @return string
	 */
	public static function getLevelName( int $level ): string
	{
		return self::$levels[$level] ?? self::$levels[self::ERROR];
	}

	/**
	 * Is high log level
	 *
	 * @param int $level
	 * @return bool
	 */
	public static function isHighLevel( int $level ): bool
	{
		return $level > self::NOTICE;
	}

	/**
	 * @return string[]
	 */
	public static function getLevels(): array
	{
		return self::$levels;
	}

	/**
	 * Create new Log instance
	 *
	 * @param $text
	 * @param int $level
	 * @param string|null $code
	 * @return Log
	 */
	public static function log( $text, int $level = self::ERROR, ?string $code = null )
	{
		if( $text instanceof Throwable )
		{
			$log = new FileLog(
				$text->getMessage(),
				$level,
				$code ? $code : ( $text instanceof ThrowableInterface ? $text->getCodeName() : (string) $text->getCode() ),
				$text->getFile(),
				$text->getLine()
			);
			$log->bounceBack();
		}

		else if( $text instanceof Text )
		{
			$log = new Log($text->getText(), $level, $code);
			$log->setReplacement($text->getReplacement());
		}

		else
		{
			$log = new Log($text, $level, $code);
		}

		return $log;
	}

	private function __construct() {}
}