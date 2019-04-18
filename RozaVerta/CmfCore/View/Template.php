<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2016
 * Time: 19:34
 */

namespace RozaVerta\CmfCore\View;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\View\Interfaces\PackageInterface;

class Template implements Interfaces\TemplateInterface, VarExportInterface
{
	private $name;

	private $package;

	private $properties = [];

	public function __construct(Package $package, string $name, array $properties = [])
	{
		$this->package = $package;
		$this->name = $name;
		$this->properties = $properties;
	}

	/**
	 * @return PackageInterface
	 */
	public function getPackage(): PackageInterface
	{
		return $this->package;
	}

	/**
	 * @return string
	 */
	public function getPathname(): string
	{
		return $this->package->getChunkFilename( $this->name );
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Template properties
	 *
	 * @return array
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * Get template package ID
	 *
	 * @return int
	 */
	public function getPackageId(): int
	{
		return $this->package->getId();
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			"name" => $this->getName(),
			"packageId" => $this->getPackageId(),
			"properties" => $this->getProperties()
		];
	}

	public function getArrayForVarExport(): array
	{
		return $this->toArray();
	}

	static public function __set_state( $data )
	{
		return new Template( Package::package($data["packageId"]), $data["name"], $data["properties"] );
	}
}