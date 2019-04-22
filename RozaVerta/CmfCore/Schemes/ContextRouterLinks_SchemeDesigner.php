<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 16:28
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;

class ContextRouterLinks_SchemeDesigner extends SchemeDesigner
{
	/**
	 * @return int
	 */
	public function getId(): int { return $this->items["id"]; }

	/**
	 * @return int
	 */
	public function getContextId(): int { return $this->items["context_id"]; }

	/**
	 * @return int
	 */
	public function getRouterId(): int { return $this->items["router_id"]; }

	protected function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format($items, $platform);
		$items["id"] = (int) $items["id"];
		$items["context_id"] = (int) $items["context_id"];
		$items["router_id"] = (int) $items["router_id"];
		return $items;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "context_router_links";
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
				"id", "router_id", "context_id"
			]
		];
	}
}