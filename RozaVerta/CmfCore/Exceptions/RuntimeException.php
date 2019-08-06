<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:22
 */

namespace RozaVerta\CmfCore\Exceptions;

use RozaVerta\CmfCore\Interfaces\ThrowableInterface;
use Throwable;

class RuntimeException extends \RuntimeException implements ThrowableInterface
{
	use CodeNameTrait;

	public function __construct( string $message = "", ?Throwable $previous = null )
	{
		parent::__construct( $message, 1500, $previous );
		$this->setCodeName(null);
	}
}