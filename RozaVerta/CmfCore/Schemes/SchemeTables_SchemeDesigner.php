<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 11:47
 */

namespace RozaVerta\CmfCore\Schemes;

class SchemeTables_SchemeDesigner extends ModuleSchemeDesigner
{
	/** @return int */
	public function getId(): int { return $this->items["id"]; }

	/** @return string */
	public function getName(): string { return $this->items["name"]; }

	/** @return string */
	public function getTitle(): string { return $this->items["title"]; }

	/** @return string */
	public function getDescription(): string { return $this->items["description"]; }

	/** @return string */
	public function getVersion(): string { return $this->items["version"]; }

	/**
	 * Get table name
	 *
	 * @return string
	 */
	public static function getTableName(): string
	{
		return "scheme_tables";
	}

	/**
	 * Get schema for query builder
	 *
	 * @return array
	 */
	public static function getSchemaBuilder(): array
	{
		return [
			"select" => [ "id", "name", "title", "description", "module_id", "version" ]
		];
	}
}