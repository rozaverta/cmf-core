<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.05.2019
 * Time: 19:38
 */

namespace RozaVerta\CmfCore\Database;

use Doctrine\DBAL\DBALException;
use RozaVerta\CmfCore\Helper\Str;
use Throwable;

/**
 * Trait DetectsLostConnections
 *
 * @package RozaVerta\CmfCore\Database
 */
trait DetectsLostConnections
{
	/**
	 * Determine if the given exception was caused by a lost connection.
	 *
	 * @param  \Throwable  $e
	 *
	 * @return bool
	 */
	protected function causedByLostConnection(Throwable $e)
	{
		$message = $e->getMessage();

		if( $e instanceof DBALException )
		{
			$prev = $e->getPrevious();
			if($prev)
			{
				$message = $prev->getMessage();
			}
		}

		return Str::contains($message, [
			'server has gone away',
			'no connection to the server',
			'Lost connection',
			'is dead or not enabled',
			'Error while sending',
			'decryption failed or bad record mac',
			'server closed the connection unexpectedly',
			'SSL connection has been closed unexpectedly',
			'Error writing data to the connection',
			'Resource deadlock avoided',
			'Transaction() on null',
			'child connection forced to terminate due to client_idle_limit',
			'query_wait_timeout',
			'reset by peer',
			'Physical connection is not usable',
			'TCP Provider: Error code 0x68',
			'ORA-03114',
			'Packets out of order. Expected',
			'Adaptive Server connection failed',
			'Communication link failure',
		]);
	}
}