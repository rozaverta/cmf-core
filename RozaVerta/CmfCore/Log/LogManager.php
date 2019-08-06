<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:25
 */

namespace RozaVerta\CmfCore\Log;

use ErrorException;
use RozaVerta\CmfCore\Exceptions\AccessException;
use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use RozaVerta\CmfCore\Filesystem\Traits\WriteFileTrait;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Log\Interfaces\LoggableInterface;
use RozaVerta\CmfCore\Log\Traits\LoggableTrait;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;
use RozaVerta\CmfCore\Support\Text;

/**
 * Class LogManager
 *
 * @package RozaVerta\CmfCore\Log
 */
final class LogManager implements LoggableInterface
{
	use SingletonInstanceTrait;
	use WriteFileTrait;
	use LoggableTrait {
		addLog as private log;
	}

	private $level = 0;

	private $levels = [];

	private $severities = [
		E_ERROR, E_WARNING,
		E_CORE_ERROR, E_CORE_WARNING,
		E_COMPILE_ERROR, E_COMPILE_WARNING,
		E_USER_ERROR, E_USER_WARNING,
		E_RECOVERABLE_ERROR
	];

	public function lastPhp()
	{
		$error = error_get_last();

		if( !isset($error['message']))
		{
			return $this;
		}

		return $this->throwable(new ErrorException(
			$error['message'],
			(int) $error["type"],
			in_array($error["type"], $this->severities) ? 1 : 0,
			$error['file'],
			$error['line']
		));
	}

	public function line( $text, int $level = Logger::ERROR, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, $level, $code ) );
	}

	public function addLog( Log $log )
	{
		static $init = false;

		if( ! $init )
		{
			$this->levels = array_flip( Logger::getLevels() );
			$this->levels["ALL"] = 0;

			$init = true;
			$level = Prop::prop("system")->getOr("debug_level", "ALL");

			if( is_int($level) )
			{
				$level = $level < 1 ? "ALL" : Logger::getLevelName($level);
			}

			if( isset($this->levels[$level]) )
			{
				$this->level = $this->levels[$level];
			}
		}

		if( $this->level === 0 || $log->getLevel() >= $this->level )
		{
			$this->log( $log->setInline() );
		}

		return $this;
	}

	/**
	 * @param \Throwable $throwable
	 * @return $this
	 */
	public function throwable( \Throwable $throwable )
	{
		$this->addLog( Logger::log($throwable) );

		if( $this->level > 0 && Logger::DEBUG < $this->level )
		{
			return $this;
		}

		$trace = $throwable->getTrace();
		for( $i = 0, $len = count($trace); $i < $len; $i++ )
		{
			$t = $trace[$i];

			$text = "Trace the " . $t["file"] . " file, line " . $t["line"] . ", ";
			if( ! empty($t["class"]) )
			{
				$text .= $t["class"];
				$text .= empty($t["type"]) ? "::" : $t["type"];
			}
			$text .= $t["function"];

			$args = array_map(static function($argument) {

				if( is_array($argument) )   return '[' . count($argument) . ']';
				if( is_bool($argument) )    return $argument ? 'true' : 'false';
				if( is_null($argument) )    return 'null';
				if( is_object($argument) )  return get_class($argument);
				if( is_string($argument) )  return '"' . addcslashes($argument, '"') . '"';
				if( is_numeric($argument) ) return $argument;
				return '<' . gettype($argument) . '>';

				}, $t["args"] ?? []);

			$text .= '( ' . implode(", ", $args) . " )";

			$this->line($text, Logger::DEBUG);
		}

		return $this;
	}

	public function flush( $length = 0 )
	{
		$cnt = $this->getLogsCount();
		if( $cnt && $cnt >= $length )
		{
			$file = Path::logs("log-" . date( "Y.m.d" ) . ".php");
			$text = file_exists($file) ? "\n" : ('<' . '?php defined(\'CMF_CORE\') || exit(\'Not access\'); ?' . ">\n");

			foreach( $this->getLogs(true) as $log )
			{
				$text .= str_replace(['<?', '?>'], ['&lt;?', '?&gt;'], $log) . "\n";
			}

			$this->writeFile($file, $text, true);
		}
	}

	public function addLogTransport( LoggableInterface $transport )
	{
		throw new AccessException("You cannot use the CacheManager as log transport");
	}

	public function __invoke( ...$args )
	{
		if( !count($args) )
		{
			return $this->lastPhp();
		}
		else
		{
			$first = $args[0];
			if( is_string($first) || $first instanceof Text )
			{
				return $this->line(...$args);
			}

			if( is_object($first) )
			{
				if( $first instanceof Log )
				{
					return $this->addLog($first);
				}

				if( $first instanceof \Throwable )
				{
					return $this->throwable($first);
				}

				if( method_exists($first, '__toString') )
				{
					$args[0] = (string) $first;
					return $this->line(...$args);
				}
			}
		}

		throw new InvalidArgumentException('Invalid parameters of the log.');
	}

	public function __destruct()
	{
		$this->flush();
	}
}