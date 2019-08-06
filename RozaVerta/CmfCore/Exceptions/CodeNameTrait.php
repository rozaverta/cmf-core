<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:25
 */

namespace RozaVerta\CmfCore\Exceptions;

use RozaVerta\CmfCore\Helper\Str;

trait CodeNameTrait
{
	private $codeName = "UNKNOWN_ERROR";

	public function setCodeName($codeName)
	{
		if(is_numeric($codeName) && $codeName > 0)
		{
			$this->codeName = "ERROR_CODE_" . $codeName;
		}
		else
		{
			if( empty($codeName) )
			{
				$codeName = get_class($this instanceof ProxyException ? $this->getPrevious() : $this);
				$end = strrpos($codeName, "\\");
				if( $end > 0 )
				{
					$codeName = substr($codeName, $end + 1);
				}
			}

			$codeName = Str::upper(Str::snake($codeName));
			$codeName = preg_replace('/[^A-Z0-9_]/', '', $codeName);
			$codeName = str_replace('_EXCEPTION', '_ERROR', $codeName);
			$codeName = trim($codeName, "_");

			if($codeName)
			{
				$this->codeName = $codeName;
			}
		}
	}

	public function getCodeName(): string
	{
		return $this->codeName;
	}
}