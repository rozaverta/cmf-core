<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:22
 */

namespace RozaVerta\CmfCore\Exceptions;

use Throwable;

class ProxyException extends Exception
{
	use CodeNameTrait;

	public function __construct( string $message, Throwable $previous )
	{
		parent::__construct( $message, $previous );
	}
}