<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 16:01
 */

namespace RozaVerta\CmfCore\Log\Traits;

use RozaVerta\CmfCore\Log\Logger;
use RozaVerta\CmfCore\Support\InvokeCounter;
use RozaVerta\CmfCore\Log\Log;
use RozaVerta\CmfCore\Log\Interfaces\LoggableInterface;

trait LoggableTrait
{
	private $logs = [];

	private $logs_capture_count = 0;

	private $logs_capture_callbacks = [];

	private $logs_is_transport = false;

	/**
	 * @var null|\RozaVerta\CmfCore\Log\Interfaces\LoggableInterface
	 */
	private $logs_transport = null;

	public function hasLogs(): bool
	{
		if( $this->logs_is_transport )
		{
			return $this->logs_transport->hasLogs();
		}
		else
		{
			return count( $this->logs ) > 0;
		}
	}

	/**
	 * Get the number of items
	 *
	 * @return int
	 */
	public function getLogsCount(): int
	{
		return count( $this->logs );
	}

	/**
	 * @param bool $clearReturn
	 * @return bool|\RozaVerta\CmfCore\Log\Log
	 */
	public function getLastLog( $clearReturn = false )
	{
		if( $this->logs_is_transport )
		{
			return $this->logs_transport->getLastLog( $clearReturn );
		}

		$count = count( $this->logs );
		if( !$count )
		{
			return false;
		}

		if( $clearReturn )
		{
			return array_pop( $this->logs );
		}
		else
		{
			return $this->logs[ $count - 1 ];
		}
	}

	/**
	 * Get all logs
	 *
	 * @param bool $clear
	 * @return \RozaVerta\CmfCore\Log\Log[]
	 */
	public function getLogs( $clear = false )
	{
		if( $this->logs_is_transport )
		{
			return $this->logs_transport->getLogs( $clear );
		}

		if( $clear )
		{
			if( count( $this->logs ) )
			{
				$logs = $this->logs;
				$this->cleanLogs();
				return $logs;
			}
			else
			{
				return [];
			}
		}
		else
		{
			return $this->logs;
		}
	}

	/**
	 * @param \Closure $capture
	 * @return $this
	 */
	public function listenLog( \Closure $capture )
	{
		if( $this->logs_is_transport )
		{
			$this->logs_transport->listenLog( $capture );
		}
		else
		{
			$index = array_search( $capture, $this->logs_capture_callbacks, true );
			if( $index === false )
			{
				$this->logs_capture_callbacks[] = $capture;
				$this->logs_capture_count++;
			}
		}

		return $this;
	}

	/**
	 * @param $transport
	 * @return $this
	 */
	public function addLogTransport( LoggableInterface $transport )
	{
		if( $this->logs_is_transport )
		{
			if( $this->logs_transport !== $transport )
			{
				throw new \RuntimeException( "This class already uses a different transport" );
			}
		}
		else if( !$transport->hasLogTransport( $this ) )
		{
			$this->logs_is_transport = true;
			$this->logs_transport = $transport;

			// move logs
			if( count( $this->logs ) )
			{
				foreach( $this->logs as $log )
				{
					$transport->addLog( $log );
				}
				$this->logs = [];
			}

			// move capture
			if( $this->logs_capture_count > 0 )
			{
				for( $i = 0; $i < $this->logs_capture_count; $i++ )
				{
					$transport->listenLog( $this->logs_capture_callbacks[ $i ] );
				}
				$this->logs_capture_callbacks = [];
			}
		}

		return $this;
	}

	/**
	 * @return $this
	 */
	public function removeLogTransport()
	{
		if( $this->logs_is_transport )
		{
			$this->logs_is_transport = false;
			$this->logs_transport = null;
		}
		return $this;
	}

	public function hasLogTransport( LoggableInterface $transport = null )
	{
		if( is_null( $transport ) )
		{
			return $this->logs_is_transport;
		}
		else
		{
			return $transport === $this || $this->logs_is_transport && $this->logs_transport->hasLogTransport( $transport );
		}
	}

	/**
	 * @param \RozaVerta\CmfCore\Log\Log $log
	 * @return $this
	 */
	public function addLog( Log $log )
	{
		// use transporter
		if( $this->logs_is_transport )
		{
			$this->logs_transport->addLog( $log );
		}

		// capture log
		else if( $this->logs_capture_count > 0 )
		{
			$counter = new InvokeCounter( true );
			$native = $counter->getClosure();

			for( $i = 0; $i < $this->logs_capture_count; $i++ )
			{
				call_user_func( $this->logs_capture_callbacks[ $i ], $log, $native );
				$counter->unfreeze();
			}

			if( $counter->getCount() === $this->logs_capture_count )
			{
				$this->logs[] = $log;
			}
		}

		// add default log
		else
		{
			$this->logs[] = $log;
		}

		return $this;
	}

	/**
	 * Add debug log information
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return $this
	 */
	public function addDebug( $text, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, Logger::DEBUG, $code ) );
	}

	/**
	 * Add log for interesting events
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return $this
	 */
	public function addInfo( $text, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, Logger::INFO, $code ) );
	}

	/**
	 * Add log for uncommon events
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return $this
	 */
	public function addNotice( $text, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, Logger::NOTICE, $code ) );
	}

	/**
	 * Add log for exceptional occurrences that are not errors
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return $this
	 */
	public function addWarning( $text, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, Logger::WARNING, $code ) );
	}

	/**
	 * Add runtime error log
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return $this
	 */
	public function addError( $text, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, Logger::ERROR, $code ) );
	}

	/**
	 * Add critical condition
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return $this
	 */
	public function addCritical( $text, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, Logger::CRITICAL, $code ) );
	}

	/**
	 * Add log action must be taken immediately
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return $this
	 */
	public function addAlert( $text, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, Logger::ALERT, $code ) );
	}

	/**
	 * Add urgent alert
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return $this
	 */
	public function addEmergency( $text, ?string $code = null )
	{
		return $this->addLog( Logger::log( $text, Logger::EMERGENCY, $code ) );
	}

	/**
	 * Clean all logs
	 *
	 * @return $this
	 */
	public function cleanLogs()
	{
		// use transporter
		if( $this->logs_is_transport )
		{
			$this->logs_transport->cleanLogs();
		}

		// add default log
		else
		{
			$this->logs = [];
		}

		return $this;
	}

	/**
	 * @param LoggableInterface $logs
	 * @return $this
	 */
	public function mergeLogs( LoggableInterface $logs )
	{
		foreach( $logs->getLogs( true ) as $log )
		{
			$this->logs[] = $log;
		}
		return $this;
	}
}