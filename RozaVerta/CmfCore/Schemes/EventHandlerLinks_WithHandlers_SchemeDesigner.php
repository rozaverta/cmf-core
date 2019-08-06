<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 1:13
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use RozaVerta\CmfCore\Database\Query\Criteria;
use RozaVerta\CmfCore\Module\ModuleHelper;

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
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format($items, $platform);
		$items["priority"] = (int) $items["priority"];
		if( strpos($items["class_name"], "\\") === false )
		{
			$items["class_name"] = ModuleHelper::getNamespaceName($items["module_id"]) . "Handlers\\" . $items["class_name"];
		}
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
					"criteria" => function( Criteria $criteria ) {
						$criteria->columns( "eh.id", "ehl.handler_id" );
					},
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