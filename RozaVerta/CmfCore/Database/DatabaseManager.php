<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 18:21
 */

namespace RozaVerta\CmfCore\Database;

use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;

/**
 * Class DatabaseManager
 *
 * @method static DatabaseManager getInstance()
 *
 * @package RozaVerta\CmfCore\Database
 */
final class DatabaseManager
{
	use SingletonInstanceTrait;

	/**
	 * @var Connection[]
	 */
	private $connections = [];

	/**
	 * Create query builder for the table
	 *
	 * @param string $tableName
	 * @param null|string $alias
	 * @param null|string $connection
	 * @return Query\Builder
	 */
	public static function table( string $tableName, ?string $alias = null, ?string $connection = null ): Query\Builder
	{
		return self::getInstance()->getConnection($connection)->table($tableName, $alias);
	}

	/**
	 * Get the database connection
	 *
	 * @param null|string $connection
	 * @return Connection
	 */
	public static function connection( ?string $connection = null ): Connection
	{
		return self::getInstance()->getConnection($connection);
	}

	/**
	 * Get the database connection
	 *
	 * @param null|string $name
	 * @return Connection
	 */
	public function getConnection( ?string $name = null ): Connection
	{
		if(empty($name))
		{
			$name = "default";
		}

		if( ! $this->isLoaded($name) )
		{
			$this->connections[$name] = $this->createConnection($name);
		}

		return $this->connections[$name];
	}

	/**
	 * Connection is loaded
	 *
	 * @param string $name|null
	 * @return bool
	 */
	public function isLoaded( ?string $name = null ): bool
	{
		if( !$name )
		{
			$name = "default";
		}

		return isset( $this->connections[$name] );
	}

	/**
	 * @param string $name|null
	 * @return bool
	 */
	public function isConnected( ?string $name = null ): bool
	{
		if( !$name )
		{
			$name = "default";
		}

		return $this->isLoaded($name) && $this->connections[$name]->isConnected();
	}

	/**
	 * Close all connections.
	 */
	public function closeAll()
	{
		foreach( $this->getActiveConnections() as $conn )
		{
			$conn->close();
		}
	}

	/**
	 * @return Connection[]
	 */
	public function getConnections(): array
	{
		return $this->connections;
	}

	/**
	 * @return Connection[]
	 */
	public function getActiveConnections(): array
	{
		$conn = [];

		foreach( $this->connections as $connection )
		{
			if($connection->isConnected())
			{
				$conn[] = $connection;
			}
		}

		return $conn;
	}

	protected function createConnection( string $name )
	{
		$params = Prop::prop("db")->get($name);

		if( is_string($params) && strlen($params) )
		{
			$params = [
				'url' => $params
			];
		}

		if( !is_array($params) )
		{
			throw new NotFoundException("Database connection '{$name}' not found in configuration file");
		}

		return new Connection($params, $name);
	}
}