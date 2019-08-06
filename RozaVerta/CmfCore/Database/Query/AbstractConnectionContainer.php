<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 14:11
 */

namespace RozaVerta\CmfCore\Database\Query;

use Doctrine\DBAL\Connection as DbalConnection;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Grammar;

/**
 * Class AbstractConnectionContainer
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
abstract class AbstractConnectionContainer
{
	/**
	 * @var Connection
	 */
	protected $connection;

	/**
	 * @var DbalConnection
	 */
	protected $dbalConnection;

	/**
	 * @var Grammar
	 */
	protected $grammar;

	/**
	 * AbstractConnectionContainer constructor.
	 *
	 * @param Connection $connection
	 */
	public function __construct( Connection $connection )
	{
		$this->connection = $connection;
		$this->dbalConnection = $connection->getDbalConnection();
		$this->grammar = $connection->getGrammar();
	}

	/**
	 * Get connection.
	 *
	 * @return Connection
	 */
	public function getConnection(): Connection
	{
		return $this->connection;
	}

	/**
	 * Get DBAL Connection driver.
	 *
	 * @return DbalConnection
	 */
	public function getDbalConnection(): DbalConnection
	{
		return $this->dbalConnection;
	}

	/**
	 * Get platform Grammar
	 *
	 * @return Grammar
	 */
	public function getGrammar(): Grammar
	{
		return $this->grammar;
	}
}