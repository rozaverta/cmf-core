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

class TemplatePlugins_SchemeDesigner extends ModuleSchemeDesigner
{
	/** @return int Plugin unique identifier */
	public function getId(): int { return $this->items["id"]; }

	/** @return string Plugin name */
	public function getName(): string { return $this->items["name"]; }

	/** @return string Plugin title */
	public function getTitle(): string { return $this->items["title"]; }

	/** @return bool */
	public function isVisible(): bool { return $this->items["visible"]; }

	/** @return string Plugin class name */
	public function getClassName(): string
	{
		$className = $this->items["class_name"];
		if(strpos($className, "\\") === false)
		{
			$className = $this->getModule()->getNamespaceName() . 'Plugins\\' . $className;
		}
		return $className;
	}

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
		$items["visible"] = (bool) Type::getType(Type::BOOLEAN)->convertToPHPValue($items["visible"], $platform);
		return $items;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "template_plugins";
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
				"id", "module_id", "name", "title", "visible", "class_name"
			]
		];
	}
}