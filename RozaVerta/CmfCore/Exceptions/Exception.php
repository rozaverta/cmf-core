<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:22
 */

namespace RozaVerta\CmfCore\Exceptions;

use RozaVerta\CmfCore\Interfaces\ThrowableInterface;
use Throwable;

class Exception extends \Exception implements ThrowableInterface
{
	use CodeNameTrait;

	public function __construct( string $message = "", ?Throwable $previous = null )
	{
		parent::__construct( $message, 1100, $previous );
		$this->setCodeName(null);
	}
}