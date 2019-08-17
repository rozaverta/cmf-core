<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2019
 * Time: 3:17
 */

namespace RozaVerta\CmfCore\Helper;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;

/**
 * Class PhpVarExportProxy
 *
 * @package RozaVerta\CmfCore\Helper
 */
class PhpVarExportProxy implements VarExportInterface
{
	private $className;

	/**
	 * @var array $state
	 */
	private $state;

	private static $depth = 0;
	private static $maxDepth = 0;

	public function __construct( VarExportInterface $object )
	{
		$this->className = get_class( $object );
		$this->state = $object->getArrayForVarExport();
		self::fetch( $this->state, self::$depth + 1 );
	}

	public static function proxy( array $getArrayForVarExport, int $maxDepth = 10 ): array
	{
		self::$maxDepth = $maxDepth;
		self::fetch( $getArrayForVarExport, 0 );
		return $getArrayForVarExport;
	}

	private static function fetch( array & $getArrayForVarExport, int $depth )
	{
		foreach( $getArrayForVarExport as $name => & $test )
		{
			self::$depth = $depth;
			if( is_array( $test ) )
			{
				if( $depth < self::$maxDepth )
				{
					self::fetch( $test, $depth + 1 );
				}
				else
				{
					$test = [];
				}
			}
			else if( $test instanceof VarExportInterface && !$test instanceof PhpVarExportProxy )
			{
				$test = new PhpVarExportProxy( $test );
			}
		}
	}

	public function getArrayForVarExport(): array
	{
		return [
			"className" => $this->className,
			"state" => $this->state,
		];
	}

	static public function __set_state( $data )
	{
		$ref = new \ReflectionClass( $data["className"] );
		return $ref
			->getMethod( "__set_state" )
			->invoke( null, $data["state"] );
	}
}