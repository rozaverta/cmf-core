<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 21:04
 */

namespace RozaVerta\CmfCore\Filesystem\Exceptions;

use RozaVerta\CmfCore\Exceptions\AccessException;
use RozaVerta\CmfCore\Filesystem\Interfaces\FilesystemThrowableInterface;
use Throwable;

class PathAccessException extends AccessException implements FilesystemThrowableInterface
{
	public function __construct( string $message = "", string $filename = __FILE__, int $line = __LINE__, ?Throwable $previous = null )
	{
		if( ! strlen($message) )
		{
			$message = "Path access error";
		}
		parent::__construct( $message, $filename, $line, $previous );
	}
}