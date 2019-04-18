<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 0:56
 */

namespace RozaVerta\CmfCore\Filesystem\Exceptions;

use RozaVerta\CmfCore\Exceptions\WriteException;
use RozaVerta\CmfCore\Filesystem\Interfaces\FilesystemThrowableInterface;
use Throwable;

class FileWriteException extends WriteException implements FilesystemThrowableInterface
{
	public function __construct(string $message = "", string $filename = __FILE__, int $line = __LINE__, ?Throwable $previous = null)
	{
		if( !strlen($message) )
		{
			$message = "Cannot write the file data";
		}

		parent::__construct($message, $filename, $line, $previous);
	}
}