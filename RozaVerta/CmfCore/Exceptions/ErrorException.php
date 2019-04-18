<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:22
 */

namespace RozaVerta\CmfCore\Exceptions;

use RozaVerta\CmfCore\Interfaces\ThrowableInterface;
use Throwable;

class ErrorException extends \ErrorException implements ThrowableInterface
{
	use CodeNameTrait;

	public function __construct( string $message = "", string $filename = __FILE__, int $lineno = __LINE__, ?Throwable $previous = null )
	{
		parent::__construct( $message, 1200, 1, $filename, $lineno, $previous );
		$this->setCodeName(null);
	}
}