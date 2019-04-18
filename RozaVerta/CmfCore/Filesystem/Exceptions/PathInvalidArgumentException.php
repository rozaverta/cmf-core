<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 0:30
 */

namespace RozaVerta\CmfCore\Filesystem\Exceptions;

use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use RozaVerta\CmfCore\Filesystem\Interfaces\FilesystemThrowableInterface;
use Throwable;

class PathInvalidArgumentException extends InvalidArgumentException implements FilesystemThrowableInterface
{
	public function __construct( string $message = "", ?Throwable $previous = null )
	{
		parent::__construct( $message, $previous );
	}
}