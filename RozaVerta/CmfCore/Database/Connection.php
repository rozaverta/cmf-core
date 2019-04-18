<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 18:22
 */

namespace RozaVerta\CmfCore\Database;

use BadMethodCallException;
use Closure;
use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use RozaVerta\CmfCore\Database\Query\Builder;
use Throwable;

/**
 * Class Connection
 *
 * @method string getDatabase() Gets the name of the database this Connection is connected to.
 * @method string|null getHost() Gets the hostname of the currently connected database.
 * @method mixed getPort() Gets the port of the currently connected database.
 * @method string getUsername() Gets the username used by this connection.
 * @method string getPassword() Gets the password used by this connection.
 * @method string|null getServerVersion() Returns the database server version if the underlying driver supports it.
 * @method bool isAutoCommit() Returns the current auto-commit mode for this connection.
 * @method bool isConnected() Whether an actual connection to the database is established.
 * @method bool connect() Establishes the connection with the database.
 * @method void close() Closes the connection.
 * @method bool ping() Ping the server
 * @method bool isTransactionActive() Checks whether a transaction is currently active.
 * @method void beginTransaction() Starts a transaction by suspending auto-commit mode.
 * @method void commit() Commits the current transaction.
 * @method void commitAll() Commits all current nesting transactions.
 * @method void rollBack() Cancels any database changes done during the current transaction.
 * @method void createSavepoint(string $savepoint) Creates a new savepoint.
 * @method void releaseSavepoint(string $savepoint) Releases the given savepoint.
 * @method void rollbackSavepoint(string $savepoint) Rolls back to the given savepoint.
 * @method void setRollbackOnly() Marks the current transaction so that the only possible outcome for the transaction to be rolled back.
 * @method bool isRollbackOnly() Checks whether the current transaction is marked for rollback only.
 * @method mixed convertToDatabaseValue($value, string $type) Converts a given value to its database representation according to the conversion rules of a specific DBAL mapping type.
 * @method mixed convertToPHPValue($value, string $type) Converts a given value to its PHP representation according to the conversion rules of a specific DBAL mapping type.
 * @method \Doctrine\DBAL\Driver\Statement query(...$args) Executes an SQL statement, returning a result set as a Statement object.
 * @method int exec(string $query) Executes an SQL statement and return the number of affected rows.
 * @method \Doctrine\DBAL\Driver\ResultStatement executeQuery(string $query, array $params = [], $types = []) Executes an, optionally parametrized, SQL query.
 * @method \Doctrine\DBAL\Driver\ResultStatement executeCacheQuery(string $query, array $params = [], $types = [], ?\Doctrine\DBAL\Cache\QueryCacheProfile $qcp = null) Executes a caching query.
 * @method string|int lastInsertId(string $seqName = null) Returns the ID of the last inserted row, or the last value from a sequence object, depending on the underlying driver.
 *
 * @package RozaVerta\CmfCore\Database
 */
class Connection
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $conn;

	/**
	 * @var string
	 */
	protected $prefix = "";

	public function __construct( array $params = [], string $name )
	{
		$this->conn = DriverManager::getConnection($params);
		$this->conn->setFetchMode( FetchMode::ASSOCIATIVE );

		$this->name = $name;
		$this->prefix = $params["prefix"] ?? "";
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return DBALConnection
	 */
	public function getDbalConnection(): DBALConnection
	{
		return $this->conn;
	}

	public function getDbalDatabasePlatform(): AbstractPlatform
	{
		return $this->conn->getDatabasePlatform();
	}

	/**
	 * @param string $table
	 * @return string
	 */
	public function getTableName( string $table ): string
	{
		return $this->prefix . $table;
	}

	/**
	 * @return string
	 */
	public function getTablePrefix(): string
	{
		return $this->prefix;
	}

	public function table(string $name, string $alias = null): Builder
	{
		return new Builder([$name, $alias], $this);
	}

	/**
	 * Executes a function in a transaction.
	 *
	 * The function gets passed this Connection instance as an (optional) parameter.
	 *
	 * If an exception occurs during execution of the function or transaction commit,
	 * the transaction is rolled back and the exception re-thrown.
	 *
	 * @param Closure $func The function to execute transactionally.
	 * @param array $args Extend
	 *
	 * @return mixed The value returned by $func
	 *
	 * @throws Throwable
	 */
	public function transactional( Closure $func, ... $args )
	{
		$this->beginTransaction();
		try {
			$res = $func($this, ... $args);
			$this->conn->commit();
			return $res;
		}
		catch ( Throwable $e) {
			$this->conn->rollBack();
			throw $e;
		}
	}

	/**
	 * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
	 * and returns the number of affected rows.
	 *
	 * This method supports PDO binding types as well as DBAL mapping types.
	 *
	 * @param string         $query  The SQL query.
	 * @param mixed[]        $params The query parameters.
	 * @param int[]|string[] $types  The parameter types.
	 *
	 * @return int The number of affected rows.
	 *
	 * @throws DBALException
	 */
	public function executeWritable($query, array $params = [], array $types = [])
	{
		return $this->conn->executeUpdate($query, $params, $types);
	}

	public function __call( $name, $arguments )
	{
		switch($name) {
			case "getDatabase":
			case "getHost":
			case "getPort":
			case "getUsername":
			case "getPassword":
			case "getServerVersion":
			case "isAutoCommit":
			case "isConnected":
			case "connect":
			case "close":
			case "ping":
			case "isTransactionActive":
			case "beginTransaction":
			case "commit":
			case "commitAll":
			case "rollBack":
			case "setRollbackOnly":
			case "isRollbackOnly":
				return $this->conn->{$name}();

			case "createSavepoint":
			case "releaseSavepoint":
			case "rollbackSavepoint":
			case "convertToDatabaseValue":
			case "convertToPHPValue":
			case "query":
			case "exec":
			case "executeQuery":
			case "executeCacheQuery":
			case "lastInsertId":
				return $this->conn->{$name}( ... $arguments );
		}

		throw new BadMethodCallException("Call to undefined method " . __CLASS__ . "::{$name}()");
	}
}