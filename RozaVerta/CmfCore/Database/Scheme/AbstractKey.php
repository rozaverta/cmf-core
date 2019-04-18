<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 14.03.2019
 * Time: 7:31
 */

namespace RozaVerta\CmfCore\Database\Scheme;


abstract class AbstractKey
{
	/**
	 * @var string|null
	 */
	protected $name = null;

	/**
	 * @var array
	 */
	protected $columns = [];

	/**
	 * @return string
	 */
	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * @return array
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}

	public static function createName( string $tableName, array $columns, string $prefix = "index" )
	{
		$name = $tableName . "_{$prefix}__" . implode("_", $columns);
		if( strlen($name) > 63 )
		{
			$name = $prefix . "__" . md5($name);
		}
		return $name;
	}
}