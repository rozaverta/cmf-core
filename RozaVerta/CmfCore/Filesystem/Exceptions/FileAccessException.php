<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 21:04
 */

namespace RozaVerta\CmfCore\Filesystem\Exceptions;

use Throwable;

class FileAccessException extends PathAccessException
{
	public function __construct( string $message = "", string $filename = __FILE__, int $line = __LINE__, ?Throwable $previous = null )
	{
		if( !strlen($message) )
		{
			$message = "File access error";
		}
		parent::__construct( $message, $filename, $line, $previous );
	}
}