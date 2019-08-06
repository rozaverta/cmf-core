<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 21:11
 */

namespace RozaVerta\CmfCore\Filesystem\Traits;

use RozaVerta\CmfCore\Filesystem\Interfaces\FilesystemThrowableInterface;
use RozaVerta\CmfCore\Log\Interfaces\LoggableInterface;
use RozaVerta\CmfCore\Log\LogManager;

trait ErrorGetterTrait
{
	protected function getError( FilesystemThrowableInterface $e): bool
	{
		$log = LogManager::getInstance();
		$log->lastPhp();

		if( $this instanceof LoggableInterface )
		{
			$this->addError($e->getMessage());
		}
		else
		{
			$log->throwable($e);
		}

		return false;
	}
}