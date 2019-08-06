<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 1:13
 */

namespace RozaVerta\CmfCore\Schemes;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

/**
 * Class Events_SchemeDesigner
 *
 * @package RozaVerta\CmfCore\Schemes
 */
class Events_SchemeDesigner extends ModuleSchemeDesigner
{
	/** @return int */
	public function getId(): int { return $this->items["id"]; }

	/** @return string */
	public function getName(): string { return $this->items["name"]; }

	/** @return string */
	public function getTitle(): string { return $this->items["title"]; }

	/** @return bool */
	public function isCompletable(): bool { return $this->items["completable"]; }

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
		$items["completable"] = (bool) Type::getType(Type::BOOLEAN)->convertToPHPValue($items["completable"], $platform);
		return $items;
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "events";
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
				"id", "name", "title", "module_id", "completable"
			]
		];
	}
}