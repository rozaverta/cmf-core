<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 16:28
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class ContextModuleLinks_SchemeDesigner extends ModuleSchemeDesigner
{
	/**
	 * @return int
	 */
	public function getId(): int { return $this->items["id"]; }

	/**
	 * @return int
	 */
	public function getContextId(): int { return $this->items["context_id"]; }

	protected function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format($items, $platform);
		$items["id"] = (int) $items["id"];
		$items["context_id"] = (int) $items["context_id"];
		return $items;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "context_module_links";
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
				"id", "module_id", "context_id"
			]
		];
	}
}