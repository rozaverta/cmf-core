<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.09.2017
 * Time: 3:19
 */

namespace RozaVerta\CmfCore\Exceptions;

use Throwable;

class NotFoundException extends Exception
{
	public function __construct(string $message = "", ?Throwable $previous = null)
	{
		if( !strlen($message) )
		{
			$message = "Not found";
		}

		parent::__construct( $message, $previous );
	}
}