<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 28.04.2019
 * Time: 13:13
 */

namespace RozaVerta\CmfCore\Exceptions;

use RozaVerta\CmfCore\Helper\Str;
use Throwable;

class ValidateException extends InvalidArgumentException
{
	public function __construct( string $message = "", string $errorNameSuffix = "", ?Throwable $previous = null )
	{
		parent::__construct(empty($message) ? "Validate error" : $message, $previous );

		if($errorNameSuffix)
		{
			$errorNameSuffix = Str::upper(Str::snake($errorNameSuffix));
			if($errorNameSuffix[0] !== "_")
			{
				$errorNameSuffix = "_";
			}
			if(strpos($errorNameSuffix, "ERROR") === false)
			{
				$errorNameSuffix .= "_ERROR";
			}
			$codeName = $this->getCodeName();
			$this->setCodeName(str_replace("_ERROR", $errorNameSuffix, $codeName));
		}
	}
}