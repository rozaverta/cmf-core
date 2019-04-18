<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 21:57
 */

namespace RozaVerta\CmfCore\Filesystem\Exceptions;

use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Filesystem\Interfaces\FilesystemThrowableInterface;
use Throwable;

class FileNotFoundException extends NotFoundException implements FilesystemThrowableInterface
{
	public function __construct($message = "", Throwable $previous = null)
	{
		if( !strlen($message) )
		{
			$message = "File not found";
		}

		parent::__construct( $message, $previous );
	}
}