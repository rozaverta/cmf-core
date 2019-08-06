<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 30.04.2018
 * Time: 19:09
 */

namespace RozaVerta\CmfCore\Log\Interfaces;

use RozaVerta\CmfCore\Log\Log;

interface LoggableInterface
{
	/**
	 * Add new log instance
	 *
	 * @param Log $log
	 * @return mixed
	 */
	public function addLog( Log $log );

	/**
	 * Add debug log information
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return mixed
	 */
	public function addDebug( $text, ?string $code = null );

	/**
	 * Add log for interesting events
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return mixed
	 */
	public function addInfo( $text, ?string $code = null );

	/**
	 * Add log for uncommon events
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return mixed
	 */
	public function addNotice( $text, ?string $code = null );

	/**
	 * Add log for exceptional occurrences that are not errors
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return mixed
	 */
	public function addWarning( $text, ?string $code = null );

	/**
	 * Add runtime error log
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return mixed
	 */
	public function addError( $text, ?string $code = null );

	/**
	 * Add critical condition
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return mixed
	 */
	public function addCritical( $text, ?string $code = null );

	/**
	 * Add log action must be taken immediately
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return mixed
	 */
	public function addAlert( $text, ?string $code = null );

	/**
	 * Add urgent alert
	 *
	 * @param string | \RozaVerta\CmfCore\Support\Text $text
	 * @param string|null $code
	 * @return mixed
	 */
	public function addEmergency( $text, ?string $code = null );

	/**
	 * @return bool
	 */
	public function hasLogs(): bool;

	/**
	 * Get logs count
	 *
	 * @return int
	 */
	public function getLogsCount(): int;

	/**
	 * Get last log instance
	 *
	 * @param bool $clearReturn
	 * @return bool|Log
	 */
	public function getLastLog( $clearReturn = false );

	/**
	 * Get all logs as array
	 *
	 * @param bool $clear
	 * @return mixed
	 */
	public function getLogs( $clear = false );

	/**
	 * Clean all logs
	 *
	 * @return mixed
	 */
	public function cleanLogs();

	/**
	 * @param \Closure $capture
	 * @return $this
	 */
	public function listenLog( \Closure $capture);

	/**
	 * @param LoggableInterface $transport
	 * @return $this
	 */
	public function addLogTransport( LoggableInterface $transport );

	/**
	 * @return $this
	 */
	public function removeLogTransport();

	/**
	 * @param LoggableInterface|null $transport
	 * @return mixed
	 */
	public function hasLogTransport( LoggableInterface $transport = null );
}