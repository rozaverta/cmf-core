<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.08.2018
 * Time: 17:42
 */

namespace RozaVerta\CmfCore\Exceptions;

use Throwable;

class WriteException extends ErrorException
{
	public function __construct(string $message = "", string $filename = __FILE__, int $line = __LINE__, ?Throwable $previous = null)
	{
		if( !strlen($message) )
		{
			$message = "Write error";
		}

		parent::__construct($message, $filename, $line, $previous);
	}
}