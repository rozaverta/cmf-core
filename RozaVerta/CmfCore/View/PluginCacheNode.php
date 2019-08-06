<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2019
 * Time: 13:03
 */

namespace RozaVerta\CmfCore\View;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;

class PluginCacheNode implements VarExportInterface
{
	private $name;

	private $data;

	private $key;

	public function __construct( string $name, array $data, string $key )
	{
		$this->name = $name;
		$this->data = $data;
		$this->key = $key;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function getKey(): string
	{
		return $this->key;
	}

	public function getArrayForVarExport(): array
	{
		return [
			"name" => $this->name,
			"data" => $this->data,
			"key"  => $this->key,
		];
	}

	static public function __set_state( $data )
	{
		return new PluginCacheNode(
			$data["name"],
			$data["data"],
			$data["key"]
		);
	}
}