<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 1:13
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;

/**
 * Class EventHandlerLinks_SchemeDesigner
 *
 * @package RozaVerta\CmfCore\Schemes
 */
class EventHandlerLinks_SchemeDesigner extends SchemeDesigner
{
	/** @return int */
	public function getId(): int { return $this->items["id"]; }

	/** @return int */
	public function getEventId(): int { return $this->items["event_id"]; }

	/** @return int */
	public function getHandlerId(): int { return $this->items["handler_id"]; }

	/** @return int */
	public function getPriority(): int { return $this->items["priority"]; }

	/**
	 * @param array $items
	 * @param AbstractPlatform $platform
	 * @return array
	 */
	public function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format($items, $platform);
		$items["id"] = (int) $items["id"];
		$items["event_id"] = (int) $items["event_id"];
		$items["handler_id"] = (int) $items["handler_id"];
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
		return "event_handler_links";
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
				"id", "event_id", "handler_id", "priority"
			]
		];
	}
}