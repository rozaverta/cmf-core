<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 11:47
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class SchemeTables_SchemeDesigner extends ModuleSchemeDesigner
{
	/** @return int */
	public function getId(): int { return $this->items["id"]; }

	/** @return string */
	public function getName(): string { return $this->items["name"]; }

	/** @return string */
	public function getTitle(): string { return $this->items["title"]; }

	/** @return string */
	public function getDescription(): string { return $this->items["description"]; }

	/** @return bool */
	public function isAddon(): bool { return $this->items["addon"]; }

	/** @return string */
	public function getVersion(): string { return $this->items["version"]; }

	/**
	 * @param array $items
	 * @param AbstractPlatform $platform
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format($items, $platform);
		$items["id"] = (int) $items["id"];
		if( !is_bool($items["addon"]) ) $items["addon"] = Type::getType(Type::BOOLEAN)->convertToPHPValue($items["addon"], $platform);
		return $items;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "scheme_tables";
	}

	/**
	 * Get schema for query builder
	 *
	 * @return array
	 */
	public static function getSchemaBuilder(): array
	{
		return [
			"select" => [ "id", "name", "title", "description", "module_id", "addon", "version" ]
		];
	}
}