<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.03.2019
 * Time: 10:42
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;
use RozaVerta\CmfCore\Module\ModuleManifest;
use RozaVerta\CmfCore\Module\Exceptions\ModuleBadNameException;
use RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException;

/**
 * Class Modules_SchemeDesigner
 *
 * @package RozaVerta\CmfCore\Schemes
 */
class Modules_SchemeDesigner extends SchemeDesigner
{
	/** @return int */
	public function getId(): int { return $this->items["id"]; }

	/** @return string */
	public function getName(): string { return $this->items["name"]; }

	/** @return string */
	public function getNamespaceName(): string { return $this->items["namespace_name"]; }

	/** @return string */
	public function getVersion(): string { return $this->items["version"]; }

	/** @return bool */
	public function isInstall(): bool { return $this->items["install"]; }

	/** @return array */
	public function getExtra(): array { return $this->items["extra"]; }

	/** @return string */
	public function getKey(): string { return $this->items["key"]; }

	/** @return string */
	public function getPathname(): string { return $this->items["pathname"]; }

	/**
	 * @var ModuleManifest
	 */
	protected $manifest;

	/**
	 * @param array $items
	 * @param AbstractPlatform $platform
	 * @return array
	 * @throws ModuleNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	protected function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format($items, $platform);
		$items["id"] = (int) $items["id"];
		if( ! is_bool($items["install"]) ) $items["install"] = (bool) Type::getType(Type::BOOLEAN)->convertToPHPValue($items["install"], $platform);

		$manifestClassName = trim($items["namespace_name"], '\\') . '\Manifest';
		if( ! class_exists($manifestClassName, true) )
		{
			throw new ModuleNotFoundException("The '{$items['name']}' module not found");
		}

		$this->manifest = new $manifestClassName();
		if( $this->manifest->getName() !== $items["name"] )
		{
			throw new ModuleBadNameException("Failure manifest data for module '{$items['name']}'");
		}

		$items["extra"] = $this->manifest->getExtras();
		$items["key"] = $this->manifest->getKey();
		$items["namespace_name"] = $this->manifest->getNamespaceName();
		$items["pathname"] = $this->manifest->getPathname();

		return $items;
	}

	/**
	 * Get module manifest file
	 *
	 * @return \RozaVerta\CmfCore\Module\ModuleManifest
	 */
	public function getManifest(): ModuleManifest
	{
		if( !isset($this->manifest) )
		{
			$manifestClassName = $this->getNamespaceName() . 'Manifest';
			$this->manifest = new $manifestClassName();
		}
		return $this->manifest;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "modules";
	}

	/**
	 * Get schema for query builder
	 *
	 * @return array
	 */
	public static function getSchemaBuilder(): array
	{
		return [
			"select" => [
				"id", "name", "namespace_name", "version", "install"
			]
		];
	}
}