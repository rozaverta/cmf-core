<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 18:21
 */

namespace RozaVerta\CmfCore\Database;

use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;
use Throwable;

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
	 */
	public static function table( string $tableName, ?string $alias = null, ?string $connection = null )
	{
		return null;
	}

	/**
	 * Create new plain builder.
	 *
	 * @param string|null $connection
	 *
	 * @return Query\PlainBuilder
	 *
	 * @throws NotFoundException
	 */
	public static function plainBuilder( ?string $connection = null ): Query\PlainBuilder
	{
		return self::getInstance()->getConnection( $connection )->plainBuilder();
	}

	/**
	 * Create new query builder.
	 *
	 * @param string      $table
	 * @param string|null $alias
	 * @param string|null $connection
	 *
	 * @return Query\Builder
	 *
	 * @throws Throwable
	 */
	public static function builder( string $table, ?string $alias = null, ?string $connection = null ): Query\Builder
	{
		return self::getInstance()->getConnection( $connection )->builder( $table, $alias );
	}

	/**
	 * Create new SchemeDesigner fetch query builder.
	 *
	 * @param string      $className
	 *
	 * @param string|null $connection
	 *
	 * @return Query\SchemeDesignerFetchBuilder
	 *
	 * @throws Throwable
	 */
	public static function schemeDesignerFetchBuilder( string $className, ?string $connection = null ): Query\SchemeDesignerFetchBuilder
	{
		return self::getInstance()->getConnection( $connection )->schemeDesignerFetchBuilder( $className );
	}

	/**
	 * Get the database connection
	 *
	 * @param null|string $connection
	 *
	 * @return Connection
	 *
	 * @throws NotFoundException
	 */
	public static function connection( ?string $connection = null ): Connection
	{
		return self::getInstance()->getConnection($connection);
	}

	/**
	 * Get the database connection
	 *
	 * @param null|string $name
	 *
	 * @return Connection
	 *
	 * @throws NotFoundException
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