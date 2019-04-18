<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 3:19
 */

namespace RozaVerta\CmfCore\Exceptions;

use Throwable;

class ReadException extends ErrorException
{
	public function __construct(string $message = "", string $filename = __FILE__, int $line = __LINE__, Throwable $previous = null)
	{
		if( !strlen($message) )
		{
			$message = "Read error";
		}

		parent::__construct($message, $filename, $line, $previous);
	}
}