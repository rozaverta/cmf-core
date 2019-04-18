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
	 * Package title.
	 *
	 * @return string
	 */
	public function getDescription(): string { return $this->items["description"]; }

	/**
	 * Package version.
	 *
	 * @return string
	 */
	public function getVersion(): string { return $this->items["version"]; }

	/**
	 * Package author
	 *
	 * @return string
	 */
	public function getAuthor(): string { return $this->items["author"]; }

	/**
	 * Package url link
	 *
	 * @return string
	 */
	public function getLink(): string { return $this->items["link"]; }

	/**
	 * Package readme.md data text.
	 *
	 * @return string
	 */
	public function getReadme(): string { return $this->items["readme"]; }

	/**
	 * Package license.
	 *
	 * @return string
	 */
	public function getLicense(): string { return $this->items["license"]; }

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
				"id", "module_id", "name", "description", "version", "author", "link", "readme", "license"
			]
		];
	}
}