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
 * Class EventHandlerLinks_WithHandlers_SchemeDesigner
 *
 * @package RozaVerta\CmfCore\Schemes
 */
class EventHandlerLinks_WithHandlers_SchemeDesigner extends ModuleSchemeDesigner
{
	/** @return int */
	public function getPriority(): int { return $this->items["priority"]; }

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
		$items["priority"] = (int) $items["priority"];
		return $items;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return EventHandlerLinks_SchemeDesigner::getTableName();
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
				"eh.module_id", "eh.class_name", "ehl.priority"
			],
			"alias" => "ehl",
			"joins" => [
				[
					"tableName" => EventHandlers_SchemeDesigner::getTableName(),
					"alias" => "eh",
					"type" => "left",
					"criteria" => "eh.id = ehl.handler_id"
				]
			],
			"orderBy" => ["event_id", "priority"],
			"groupBy" => "handler_id",
			"columns" => [
				"module_id" => "eh.module_id",
				"class_name" => "eh.class_name",
				"handler_id" => "ehl.handler_id",
				"event_id" => "ehl.event_id",
				"priority" => "ehl.priority"
			]
		];
	}
}