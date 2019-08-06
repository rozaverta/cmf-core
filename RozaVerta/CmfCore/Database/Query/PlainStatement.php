<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.06.2019
 * Time: 11:46
 */

namespace RozaVerta\CmfCore\Database\Query;

use Doctrine\DBAL\ParameterType;
use RozaVerta\CmfCore\Database\Connection;

/**
 * Class PlainStatement
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
class PlainStatement
{
	protected $connection;

	/**
	 * @var \Doctrine\DBAL\Driver\Statement
	 */
	protected $statement;

	protected $query;

	protected $params;

	protected $types;

	protected $values;

	protected $writable = false;

	protected $closed = false;

	public function __construct( Connection $connection, bool $writable, string $query, array $params, array $types, array $values = [] )
	{
		$this->connection = $connection;
		$this->statement = $connection->getDbalConnection()->getWrappedConnection()->prepare( $query );
		$this->writable = $writable;
		$this->query = $query;
		$this->params = $params;
		$this->types = $types;
		$this->values = $values;

		foreach( $params as $name => $value )
		{
			$this->statement->bindValue( $name, $value, $params[$name] ?? ParameterType::STRING );
		}
	}

	/**
	 * @return Connection
	 */
	public function getConnection(): Connection
	{
		return $this->connection;
	}

	/**
	 * @return \Doctrine\DBAL\Driver\Statement
	 */
	public function getStatement(): \Doctrine\DBAL\Driver\Statement
	{
		return $this->statement;
	}

	/**
	 * @return string
	 */
	public function getSql(): string
	{
		return $this->query;
	}

	/**
	 * @return array
	 */
	public function getParams(): array
	{
		return $this->params;
	}

	/**
	 * @return array
	 */
	public function getTypes(): array
	{
		return $this->types;
	}

	/**
	 * @return bool
	 */
	public function isWritable(): bool
	{
		return $this->writable;
	}

	/**
	 * @return bool
	 */
	public function isClosed(): bool
	{
		return $this->closed;
	}

	/**
	 * @param array $params
	 * @param array $values
	 *
	 * @return \Doctrine\DBAL\Driver\Statement|int
	 */
	public function execute( array $params = [], array $values = [] )
	{
		if( $this->closed )
		{
			// todo throw new error
		}

		$glob = $this->params;

		foreach( $params as $name => $value )
		{
			if( is_string( $name ) && $name[0] !== ":" )
			{
				$name = ":" . $name;
			}

			if( isset( $glob[$name] ) )
			{
				$glob[$name] = $value;
			}
		}

		foreach( $values as $name => $value )
		{
			if( isset( $this->values[$name] ) )
			{
				$glob[$this->values[$name]] = $value;
			}
		}

		$this->statement->execute( $glob );
		if( $this->writable )
		{
			return (int) $this->statement->rowCount();
		}
		else
		{
			return $this->statement;
		}
	}

	public function lastInsertId()
	{
		return $this->connection->lastInsertId();
	}

	public function close()
	{
		if( !$this->closed )
		{
			$this->closed = true;
			$this->statement->closeCursor();
			$this->statement = null;
		}

		return $this;
	}

	public function __destruct()
	{
		$this->close();
	}
}