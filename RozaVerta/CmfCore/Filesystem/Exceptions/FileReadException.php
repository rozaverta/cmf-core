<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 20:54
 */

namespace RozaVerta\CmfCore\Filesystem\Exceptions;

use RozaVerta\CmfCore\Exceptions\ReadException;
use RozaVerta\CmfCore\Filesystem\Interfaces\FilesystemThrowableInterface;
use Throwable;

class FileReadException extends ReadException implements FilesystemThrowableInterface
{
	public function __construct(string $message = "", string $filename = __FILE__, int $line = __LINE__, ?Throwable $previous = null )
	{
		if( !strlen($message) )
		{
			$message = "Cannot read the file";
		}
		parent::__construct( $message, $filename, $line, $previous );
	}
}