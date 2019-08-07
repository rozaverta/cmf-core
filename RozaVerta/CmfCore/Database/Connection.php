<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 18:22
 */

namespace RozaVerta\CmfCore\Database;

use BadMethodCallException;
use Closure;
use Doctrine\DBAL\Connection as DBALConnection;
use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\ResultStatement;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\FetchMode;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use RozaVerta\CmfCore\Database\Query\Builder;
use RozaVerta\CmfCore\Database\Query\Grammars\MySqlGrammar;
use RozaVerta\CmfCore\Database\Query\Grammars\PostgresGrammar;
use RozaVerta\CmfCore\Database\Query\Grammars\SQLiteGrammar;
use RozaVerta\CmfCore\Database\Query\Grammars\SqlServerGrammar;
use RozaVerta\CmfCore\Database\Query\PlainBuilder;
use RozaVerta\CmfCore\Database\Query\SchemeDesignerFetchBuilder;
use Throwable;

/**
 * Class Connection
 *
 * @method string getDatabase() Gets the name of the database this Connection is connected to.
 * @method string|null getHost() Gets the hostname of the currently connected database.
 * @method mixed getPort() Gets the port of the currently connected database.
 * @method string getUsername() Gets the username used by this connection.
 * @method string getPassword() Gets the password used by this connection.
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
	use DetectsLostConnections;

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

	/**
	 * @var Grammar
	 */
	protected $grammar;

	/**
	 * Connection constructor.
	 *
	 * @param array $params
	 * @param string $name
	 * @throws DBALException
	 */
	public function __construct( array $params = [], string $name = "default" )
	{
		$this->conn = DriverManager::getConnection($params);
		$this->conn->setFetchMode( FetchMode::ASSOCIATIVE );

		$this->name = $name;
		$this->prefix = $params["prefix"] ?? "";
		$this->loadGrammar();
	}

	/**
	 * Get connection configuration name.
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get DBAL Connection.
	 *
	 * @return DBALConnection
	 */
	public function getDbalConnection(): DBALConnection
	{
		return $this->conn;
	}

	/**
	 * Get DBAL Platform.
	 *
	 * @return AbstractPlatform
	 *
	 * @throws DBALException
	 */
	public function getDbalDatabasePlatform(): AbstractPlatform
	{
		return $this->conn->getDatabasePlatform();
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

	/**
	 * Get the full table name with the table prefix.
	 *
	 * @param string $table
	 * @return string
	 */
	public function getTableName( string $table ): string
	{
		return $this->prefix . $table;
	}

	/**
	 * Get table prefix.
	 *
	 * @return string
	 */
	public function getTablePrefix(): string
	{
		return $this->prefix;
	}

	/**
	 * Create new query builder.
	 *
	 * @param string $name
	 * @param string|null $alias
	 *
	 * @deprecated
	 */
	public function table( string $name, string $alias = null )
	{
		return null;
	}

	/**
	 * Create new plain builder.
	 *
	 * @return PlainBuilder
	 */
	public function plainBuilder(): PlainBuilder
	{
		return new PlainBuilder( $this );
	}

	/**
	 * Create new query builder.
	 *
	 * @param string      $table
	 * @param string|null $alias
	 *
	 * @return Builder
	 *
	 * @throws Throwable
	 */
	public function builder( string $table, ?string $alias = null ): Builder
	{
		return new Builder( $this, $table, $alias );
	}

	/**
	 * Create new SchemeDesigner fetch query builder.
	 *
	 * @param string $className
	 *
	 * @return SchemeDesignerFetchBuilder
	 *
	 * @throws Throwable
	 */
	public function schemeDesignerFetchBuilder( string $className ): SchemeDesignerFetchBuilder
	{
		return new SchemeDesignerFetchBuilder( $this, $className );
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
		catch ( Throwable $e ) {
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
	public function executeWritable( string $query, array $params = [], array $types = [] ): int
	{
		return (int) $this->execProxy( 'executeUpdate', [ $query, $params, $types ] );
	}

	/**
	 * Executes an SQL INSERT query with the given parameters and returns the last value of the insert identifier.
	 *
	 * @param string $query
	 * @param array  $params
	 * @param array  $types
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function executeInsertGetId( string $query, array $params = [], array $types = [] )
	{
		$this->execProxy( 'executeUpdate', [ $query, $params, $types ] );
		return $this->execProxy( 'lastInsertId' );
	}

	/**
	 * Prepares and executes an SQL query and returns the first row of the result
	 * as an associative array.
	 *
	 * @param string         $query  The SQL query.
	 * @param mixed[]        $params The query parameters.
	 * @param int[]|string[] $types  The query parameter types.
	 *
	 * @return mixed[]|false False is returned if no rows are found.
	 *
	 * @throws DBALException
	 */
	public function fetchAssoc( string $query, array $params = [], array $types = [] )
	{
		return $this->fetchOne(
			static function( ResultStatement $statement, $param ) {
				return $statement->fetch( $param );
			},
			FetchMode::ASSOCIATIVE, $query, $params, $types
		);
	}

	/**
	 * Prepares and executes an SQL query and returns the first row of the result
	 * as a numerically indexed array.
	 *
	 * @param string         $query  The SQL query to be executed.
	 * @param mixed[]        $params The prepared statement params.
	 * @param int[]|string[] $types  The query parameter types.
	 *
	 * @return mixed[]|false False is returned if no rows are found.
	 *
	 * @throws DBALException
	 */
	public function fetchArray( string $query, array $params = [], array $types = [] )
	{
		return $this->fetchOne(
			static function( ResultStatement $statement, $param ) {
				return $statement->fetch( $param );
			},
			FetchMode::NUMERIC, $query, $params, $types
		);
	}

	/**
	 * Prepares and executes an SQL query and returns the value of a single column
	 * of the first row of the result.
	 *
	 * @param string         $query  The SQL query to be executed.
	 * @param mixed[]        $params The prepared statement params.
	 * @param int            $column The 0-indexed column number to retrieve.
	 * @param int[]|string[] $types  The query parameter types.
	 *
	 * @return mixed|false False is returned if no rows are found.
	 *
	 * @throws DBALException
	 */
	public function fetchColumn( string $query, array $params = [], $column = 0, array $types = [] )
	{
		return $this->fetchOne(
			static function( ResultStatement $statement, $param ) {
				return $statement->fetchColumn( $param );
			},
			$column, $query, $params, $types
		);
	}

	/**
	 * Prepares and executes an SQL query and returns the result as an associative array.
	 *
	 * @param string         $query  The SQL query.
	 * @param mixed[]        $params The query parameters.
	 * @param int[]|string[] $types  The query parameter types.
	 *
	 * @return mixed[]
	 *
	 * @throws DBALException
	 */
	public function fetchAll( string $query, array $params = [], $types = [] )
	{
		return $this->fetchOne(
			static function( ResultStatement $statement, $param ) {
				return $statement->fetchAll( $param );
			},
			FetchMode::ASSOCIATIVE, $query, $params, $types
		);
	}

	/**
	 * Adds an driver-specific LIMIT clause to the query.
	 *
	 * @param string $query
	 * @param int    $limit
	 * @param int    $offset
	 *
	 * @return string
	 *
	 * @throws DBALException
	 */
	public function modifyLimitQuery( string $query, ? int $limit, ? int $offset = null )
	{
		return $this
			->conn
			->getDatabasePlatform()
			->modifyLimitQuery( $query, $limit, $offset );
	}

	/**
	 * Exec query from DBAL connection.
	 *
	 * @param string $method
	 * @param array $args
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	protected function execProxy(string $method, array $args = [])
	{
		try {
			$result = $this->conn->{$method}( ... $args );
		}
		catch(DBALException $e) {
			$result = $this->tryAgainIfCausedByLostConnection($e, $method, $args);
		}

		return $result;
	}

	/**
	 * Handle a query exception that occurred during query execution.
	 *
	 * @param DBALException $e
	 * @param $method
	 * @param array $args
	 * @return mixed
	 * @throws DBALException
	 */
	protected function tryAgainIfCausedByLostConnection(DBALException $e, $method, array $args = [])
	{
		if ($this->causedByLostConnection($e))
		{
			$this->conn->isConnected() && $this->conn->close();
			$this->conn->connect();
			return $this->conn->{$method}( ... $args );
		}
		throw $e;
	}

	/**
	 * Fetch first result
	 *
	 * @param Closure $closure
	 * @param         $param
	 * @param         $query
	 * @param array   $params
	 * @param array   $types
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	protected function fetchOne( \Closure $closure, $param, string $query, array $params = [], array $types = [] )
	{
		$stmt = $this->executeQuery( $query, $params, $types );
		$result = $closure( $stmt, $param );
		$stmt->closeCursor();
		return $result;
	}

	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return mixed
	 *
	 * @throws DBALException
	 */
	public function __call( $name, $arguments )
	{
		switch($name) {
			case "getDatabase":
			case "getHost":
			case "getPort":
			case "getUsername":
			case "getPassword":
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
				return $this->execProxy($name, $arguments);
		}

		throw new BadMethodCallException("Call to undefined method " . __CLASS__ . "::{$name}()");
	}

	/**
	 * Load Grammar platform object
	 *
	 * @throws DBALException
	 */
	private function loadGrammar()
	{
		$driver = $this->conn->getDriver()->getName();
		switch( $driver )
		{
			case 'pdo_mysql':
			case 'drizzle_pdo_mysql':
				$this->grammar = new MySqlGrammar( $this );
				break;

			case 'pdo_pgsql':
				$this->grammar = new PostgresGrammar( $this );
				break;

			case 'pdo_sqlite':
				$this->grammar = new SQLiteGrammar( $this );
				break;

			case 'pdo_sqlsrv':
				$this->grammar = new SqlServerGrammar( $this );
				break;

			default:
				$this->grammar = new Grammar( $this );
				break;
		}
	}
}