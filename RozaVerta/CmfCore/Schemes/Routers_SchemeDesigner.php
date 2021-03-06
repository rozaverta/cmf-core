<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 20:47
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use RozaVerta\CmfCore\Database\Query\Criteria;

class Routers_SchemeDesigner extends ModuleSchemeDesigner
{
	/** @return int Router unique identifier */
	public function getId(): int { return $this->items["id"]; }

	/** @return string Router path */
	public function getPath(): string { return $this->items["path"]; }

	/** @return string Router unique name */
	public function getName(): string { return $this->items["name"]; }

	/** @return int Router position */
	public function getPosition(): int { return $this->items["position"]; }

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
		$items["position"] = (int) $items["position"];
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
		return "routers";
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
				"tr.id", "tr.name", "tr.module_id", "tr.path", "tr.position", "tr.properties",
			],
			"alias" => "tr",
			"joins" => [
				[
					"tableName" => Modules_SchemeDesigner::getTableName(),
					"alias" => "tm",
					"mode" => "left",
					"criteria" => function( Criteria $criteria ) {
						$criteria->columns( "tr.module_id", "tm.id" );
					},
				],
			],
			"where" => [
				[ "tm.id", "!=", null ],
				"tm.install" => true,
			],
			"orderBy" => [ "position" ],
			"groupBy" => "id",
			"columns" => [
				"id" => "tr.id",
				"name" => "tr.name",
				"module_id" => "tr.module_id",
				"type" => "tr.type",
				"rule" => "tr.rule",
				"position" => "tr.position",
				"properties" => "tr.properties",
			],
		];
	}
}