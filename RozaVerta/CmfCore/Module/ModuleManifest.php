<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.07.2017
 * Time: 14:26
 */

namespace RozaVerta\CmfCore\Module;

use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Helper\Json;
use RozaVerta\CmfCore\Helper\Str;
use ReflectionClass;
use RozaVerta\CmfCore\Module\Exceptions\ModuleBadNameException;
use RozaVerta\CmfCore\Module\Exceptions\ResourceInvalidTypeException;
use RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException;
use RozaVerta\CmfCore\Module\Exceptions\ResourceReadException;
use RozaVerta\CmfCore\Module\Interfaces\ModuleManifestInterface;

abstract class ModuleManifest extends Modular implements ModuleManifestInterface
{
	/**
	 * @var array
	 */
	private $manifest = [];

	/**
	 * @var array
	 */
	private $props = [
		"name"      => "",
		"title"     => "",
		"version"   => "1.0.0",
		"support"   => [],
		"extra"     => [],
		"front"     => true,
		"route"     => false
	];

	/**
	 * ModuleManifest constructor.
	 * @throws ResourceNotFoundException
	 * @throws ResourceReadException
	 */
	public function __construct()
	{
		try {
			$ref = new ReflectionClass( $this );
		}
		catch( \ReflectionException $e ) {
			throw new ResourceNotFoundException("The manifest class load error", $e);
		}

		$this->pathname = dirname($ref->getFileName()) . DIRECTORY_SEPARATOR;
		$this->namespaceName = $ref->getNamespaceName() . "\\";

		// ready manifest json file

		$file = $this->pathname . "resources" . DIRECTORY_SEPARATOR . "manifest.json";
		if( !file_exists($file) )
		{
			throw new ResourceNotFoundException("The manifest file not found");
		}

		$raw = @ file_get_contents($file);
		if( !$raw )
		{
			throw new ResourceReadException("Cannot ready manifest file '{$this->name}'");
		}

		try {
			$manifest = Json::parse($raw, true);
			if( ! is_array($manifest) )
			{
				throw new \InvalidArgumentException("Manifest data is not array");
			}
		}
		catch( \InvalidArgumentException $e ) {
			throw new ResourceReadException("Cannot read manifest file '{$this->name}', json parser error: " . $e->getCode());
		}

		if( ! isset($manifest["type"]) || $manifest["type"] !== "#/module" )
		{
			throw new ResourceInvalidTypeException("Invalid manifest file type");
		}

		foreach($this->props as $name => $value)
		{
			$this->{$name} = isset($manifest[$name]) && gettype($manifest[$name]) === gettype($value) ? $manifest[$name] : $value;
		}

		// ready module name

		if( empty($this->name) )
		{
			$this->name = $ref->getShortName();
		}

		if( ! ModuleHelper::validName($this->name) )
		{
			throw new ModuleBadNameException("Bad module name '{$this->name}'");
		}

		// create module title

		if( empty($this->title) )
		{
			$this->title = $this->name . " module";
		}

		$this->key = Str::cache($this->name, "snake");
		$this->manifest = $manifest;
	}

	public function getDescription(): string
	{
		return isset($this->manifest["description"]) && is_string($this->manifest["description"]) ? $this->manifest["description"] : "";
	}

	public function getAuthors(): array
	{
		$authors = $this->manifest["authors"] ?? [];
		if( ! is_array($authors) )
		{
			$authors = [
				["name" => $authors]
			];
		}

		foreach($authors as & $author)
		{
			if( is_string($author) )
			{
				$author = [
					"name" => $author
				];
			}
		}

		return $authors;
	}

	public function getLicenses(): array
	{
		$license = $this->manifest["license"] ?? [];
		return Arr::wrap($license);
	}

	/**
	 * @return array
	 */
	public function getManifestData(): array
	{
		return $this->manifest;
	}
}