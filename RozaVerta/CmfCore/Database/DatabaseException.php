<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.06.2019
 * Time: 15:11
 */

namespace RozaVerta\CmfCore\Database;

use Doctrine\DBAL\DBALException;
use RozaVerta\CmfCore\Database\Interfaces\DatabaseThrowableInterface;
use RozaVerta\CmfCore\Exceptions\CodeNameTrait;
use Throwable;

/**
 * Class QueryException
 *
 * @package RozaVerta\CmfCore\Database
 */
class DatabaseException extends DBALException implements DatabaseThrowableInterface
{
	use CodeNameTrait;

	public function __construct( string $message = "", ?Throwable $previous = null )
	{
		parent::__construct( $message, 1500, $previous );
		$this->setCodeName( null );
	}

	static public function unknownSourceTable(): self
	{
		return new self( "Unknown source table of the request" );
	}

	static public function invalidQueryParameter( string $param ): self
	{
		return new self( "Invalid query parameter: " . $param );
	}
}