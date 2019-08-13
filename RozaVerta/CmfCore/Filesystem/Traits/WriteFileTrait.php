<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 0:52
 */

namespace RozaVerta\CmfCore\Filesystem\Traits;

use RozaVerta\CmfCore\Exceptions\JsonParseException;
use RozaVerta\CmfCore\Helper\Json;
use RozaVerta\CmfCore\Helper\PhpExport;
use RozaVerta\CmfCore\Filesystem\Exceptions\FileWriteException;
use RozaVerta\CmfCore\Log\Interfaces\LoggableInterface;
use RozaVerta\CmfCore\Log\LogManager;

trait WriteFileTrait
{
	use ErrorGetterTrait;

	/**
	 * Write data file from string
	 *
	 * @param string $file
	 * @param string $data
	 * @param bool   $append
	 * @return bool
	 */
	protected function writeFile( string $file, string $data, bool $append = false )
	{
		return $this->write(
			$file, $data, $append
		);
	}

	/**
	 * Write data process from the iteration callback
	 *
	 * @param string   $file
	 * @param \Closure $data
	 * @param bool     $append
	 * @return bool
	 */
	protected function writeFileProcess( string $file, \Closure $data, bool $append = false )
	{
		return $this->write(
			$file, $data, $append
		);
	}

	/**
	 * Write data file as php value (export data)
	 *
	 * @param string $file
	 * @param mixed  $data
	 * @param string $dataName
	 *
	 * @return bool
	 */
	protected function writeFileExport( string $file, $data, string $dataName = 'data' )
	{
		$ext = pathinfo( $file, PATHINFO_EXTENSION );
		$ext = strtolower( $ext );

		if( $ext === "json" )
		{
			try
			{
				$data = Json::stringify( $data );
			} catch( JsonParseException $e )
			{
				if( $this instanceof LoggableInterface )
				{
					$this->addError( $e->getMessage() );
				}
				return false;
			}
		}
		else if( $ext === "php" )
		{
			$data = '<'
				. "?php defined('CMF_CORE') || exit('Not access'); \n"
				. PhpExport::getInstance()->data( $data, $dataName, true, true )
				. "\nreturn \${$dataName};";
		}
		else if( !is_string( $data ) )
		{
			$data = print_r( $data, true );
		}

		return $this->write( $file, $data, false );
	}

	/**
	 * Write data
	 *
	 * @param string $file
	 * @param $data
	 * @param $append
	 *
	 * @return bool
	 */
	private function write( string $file, $data, $append ): bool
	{
		if( function_exists('error_clear_last') )
		{
			error_clear_last();
		}

		// check directory exists or create empty directory for file
		$path = dirname($file);
		if( ! is_dir($path) )
		{
			try
			{
				if( ! @ mkdir($path, 0755, true) )
				{
					throw new FileWriteException("Cannot create the '{$path}' directory");
				}
			} catch( FileWriteException $e )
			{
				return $this->getError($e);
			}
		}

		// check directory is writable
		if( ! is_writable($path) )
		{
			return $this->getError(new FileWriteException("Path '{$path}' is not writable"));
		}

		// detect write mode
		// ignore append flag if file is not exists
		$mode = "w+";
		if( $append )
		{
			if( file_exists( $file ) )
			{
				$mode = "a+";
			}
			else
			{
				$append = false;
			}
		}

		try {
			$handle = @ fopen( $file, $mode );
			if( ! $handle )
			{
				throw new FileWriteException("Cannot open the '{$file}' file for write");
			}

			$callable = $data instanceof \Closure;
			if( flock( $handle, LOCK_EX ) )
			{
				if( $callable )
				{
					do
					{
						$content = $data( $append );
						if( ! is_string($content) || strlen($content) < 1 )
						{
							break;
						}
						if( fwrite( $handle, $content ) === false )
						{
							throw new FileWriteException($file);
						}
					} while(true);
				}
				else if( fwrite( $handle, $data ) === false )
				{
					throw new FileWriteException($file);
				}

				fflush( $handle );
				flock(  $handle, LOCK_UN );
			}
			else
			{
				throw new FileWriteException($file);
			}
		} catch( FileWriteException $e )
		{
			if( isset($handle) && ! $append && file_exists($file) )
			{
				@ unlink($file);
			}
			return $this->getError($e);
		} finally
		{
			if( isset($handle) )
			{
				@ fclose($handle);
			}
		}

		if( $this instanceof LoggableInterface && !$this instanceof LogManager )
		{
			$this->addDebug("The '{$file}' file is successfully " . ($append ? "updated" : "created"));
		}

		return true;
	}
}