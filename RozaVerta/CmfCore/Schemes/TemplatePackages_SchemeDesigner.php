<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.09.2017
 * Time: 0:05
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;

class TemplatePackages_SchemeDesigner extends ModuleSchemeDesigner
{
	/**
	 * Plugin unique identifier.
	 *
	 * @return int
	 */
	public function getId(): int { return $this->items["id"]; }

	/**
	 * Package access name.
	 *
	 * @return string
	 */
	public function getName(): string { return $this->items["name"]; }

	/**
	 * Package version.
	 *
	 * @return string
	 */
	public function getVersion(): string { return $this->items["version"]; }

	/**
	 * @param array $items
	 * @param AbstractPlatform $platform
	 *
	 * @return array
	 */
	protected function format( array $items, AbstractPlatform $platform ): array
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
		return "template_packages";
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
				"id", "module_id", "name", "version"
			]
		];
	}
}