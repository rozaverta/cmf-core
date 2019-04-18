<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 1:13
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;

/**
 * Class EventHandlers_SchemeDesigner
 *
 * @package RozaVerta\CmfCore\Schemes
 */
class EventHandlers_SchemeDesigner extends ModuleSchemeDesigner
{
	/** @return int */
	public function getId(): int { return $this->items["id"]; }

	/** @return string */
	public function getClassName(): string { return $this->items["class_name"]; }

	/**
	 * @param array $items
	 * @param AbstractPlatform $platform
	 * @return array
	 */
	public function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format($items, $platform);
		$items["id"] = (int) $items["id"];
		return $items;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "event_handlers";
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
				"id", "module_id", "class_name", "priority"
			]
		];
	}
}