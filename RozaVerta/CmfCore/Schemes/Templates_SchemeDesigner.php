<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 20:47
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;

class Templates_SchemeDesigner extends SchemeDesigner
{
	/** @return int Template unique identifier */
	public function getId(): int { return $this->items["id"]; }

	/** @return int Template package identifier */
	public function getPackageId(): int { return $this->items["package_id"]; }

	/** @return string Template access name */
	public function getName(): string { return $this->items["name"]; }

	/** @return string Template title */
	public function getTitle(): string { return $this->items["title"]; }

	/** @return array Properties data */
	public function getProperties(): array { return $this->items["properties"]; }

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
		$items["package_id"] = (int) $items["package_id"];
		$items["properties"] = Type::getType(Type::JSON_ARRAY)->convertToPHPValue($items["properties"], $platform);
		return $items;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "templates";
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
				"id", "package_id", "name", "title", "properties"
			]
		];
	}
}