<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2018
 * Time: 20:22
 */

namespace RozaVerta\CmfCore\Filesystem\Traits;

use RozaVerta\CmfCore\Filesystem\Exceptions\FileAccessException;
use RozaVerta\CmfCore\Filesystem\Exceptions\PathInvalidArgumentException;
use RozaVerta\CmfCore\Log\Interfaces\LoggableInterface;

trait DeleteFileTrait
{
	use ErrorGetterTrait;

	protected function deleteFile( string $file ): bool
	{
		if( ! file_exists($file) )
		{
			return true;
		}

		if( ! is_file($file) )
		{
			return $this->getError(
				new PathInvalidArgumentException("The '{$file}' path is not file")
			);
		}

		try {
			if( ! @ unlink($file) )
			{
				throw new FileAccessException("Cannot delete the '{$file}' file");
			}
		}
		catch(FileAccessException $e) {
			return $this->getError($e);
		}

		if( $this instanceof LoggableInterface )
		{
			$this->addDebug("The '{$file}' file was successfully deleted");
		}

		return true;
	}
}