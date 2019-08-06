<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 20.03.2019
 * Time: 10:22
 */

namespace RozaVerta\CmfCore\Route\Exceptions;

use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Route\Interfaces\RouteThrowableInterface;
use Throwable;

class PageNotFoundException extends NotFoundException implements RouteThrowableInterface
{
	public function __construct( string $message = "", ?Throwable $previous = null )
	{
		if( !strlen($message) )
		{
			$message = "Page not found.";
		}
		parent::__construct( $message, $previous );
		$this->code = 404;
	}
}