<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2019
 * Time: 13:03
 */

namespace RozaVerta\CmfCore\View;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;

class PluginNode implements VarExportInterface
{
	private $name;

	private $body;

	private $type;

	private $data;

	private $hash;

	public function __construct( string $name, string $body, int $type, array $data, string $hash )
	{
		$this->name = $name;
		$this->body = $body;
		$this->type = $type;
		$this->data = $data;
		$this->hash = $hash;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getBody(): string
	{
		return $this->body;
	}

	public function getType(): int
	{
		return $this->type;
	}

	public function getData(): array
	{
		return $this->data;
	}

	public function getHash(): string
	{
		return $this->hash;
	}

	/**
	 * @param string $body
	 */
	public function setBody( string $body ): void
	{
		$this->body = $body;
	}

	public function createEmptyClone(): PluginNode
	{
		$clone = clone $this;
		$clone->body = "";
		return $clone;
	}

	public function getArrayForVarExport(): array
	{
		return [
			"name" => $this->name,
			"body" => $this->body,
			"type" => $this->type,
			"data" => $this->data,
			"hash" => $this->hash,
		];
	}

	static public function __set_state( $data )
	{
		return new PluginNode(
			$data["name"],
			$data["body"],
			$data["type"],
			$data["data"],
			$data["hash"]
		);
	}
}