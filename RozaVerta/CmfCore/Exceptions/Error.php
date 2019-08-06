<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:22
 */

namespace RozaVerta\CmfCore\Exceptions;

use RozaVerta\CmfCore\Interfaces\ThrowableInterface;
use Throwable;

class Error extends \Error implements ThrowableInterface
{
	use CodeNameTrait;

	public function __construct( string $message = "", ?Throwable $previous = null )
	{
		parent::__construct( $message, 2000, $previous );
		$this->setCodeName(null);
	}
}