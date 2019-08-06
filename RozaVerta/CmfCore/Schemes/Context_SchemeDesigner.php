<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 20.03.2019
 * Time: 10:38
 */

namespace RozaVerta\CmfCore\Schemes;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;
use RozaVerta\CmfCore\Database\Scheme\SchemeDesigner;

class Context_SchemeDesigner extends SchemeDesigner
{
	/**
	 * @return int
	 */
	public function getId(): int { return $this->items["id"]; }

	/**
	 * @return string
	 */
	public function getName(): string { return $this->items["name"]; }

	/**
	 * @return string
	 */
	public function getTitle(): string { return $this->items["title"]; }

	/**
	 * @return string
	 */
	public function getDescription(): string { return $this->items["description"]; }

	/**
	 * @return string
	 */
	public function getHostname(): string { return $this->items["hostname"]; }

	/**
	 * @return int
	 */
	public function getHostPort(): int { return $this->items["host_port"]; }

	/**
	 * @return bool
	 */
	public function isHostSsl(): bool { return $this->items["host_ssl"]; }

	/**
	 * @return string
	 */
	public function getPath(): string { return $this->items["path"]; }

	/**
	 * @return array
	 */
	public function getQueryPath(): array { return $this->items["query_path"]; }

	/**
	 * @return bool
	 */
	public function isDefaultContext(): bool { return $this->items["default_context"]; }

	/**
	 * @return bool
	 */
	public function isHost(): bool
	{
		return strlen($this->items["hostname"]) > 0;
	}

	/**
	 * @return bool
	 */
	public function isQueryPath(): bool
	{
		return count($this->items["queries"]) > 0;
	}

	/**
	 * @return array
	 */
	public function getQueries(): array
	{
		return $this->items["queries"];
	}

	/**
	 * @return bool
	 */
	public function isPath(): bool
	{
		return strlen($this->items["path"]) > 0;
	}

	/**
	 * @return array
	 */
	public function getProperties(): array
	{
		return $this->items["properties"];
	}

	/**
	 * @param array $items
	 * @param AbstractPlatform $platform
	 * @return array
	 * @throws \Doctrine\DBAL\DBALException
	 */
	protected function format( array $items, AbstractPlatform $platform ): array
	{
		$items = parent::format($items, $platform);

		$items["id"] = (int) $items["id"];
		$items["host_port"] = (int) $items["host_port"];
		if( ! is_bool($items["host_ssl"]) ) $items["host_ssl"] = (bool) Type::getType(Type::BOOLEAN)->convertToPHPValue($items["host_ssl"], $platform);
		if( ! is_bool($items["default_context"]) ) $items["default_context"] = (bool) Type::getType(Type::BOOLEAN)->convertToPHPValue($items["default_context"], $platform);
		$items["properties"] = Type::getType(Type::JSON_ARRAY)->convertToPHPValue($items["properties"], $platform);

		$hostname = empty($items["hostname"]) ? false : filter_var($items["hostname"], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
		if( $hostname === false )
		{
			$items["hostname"] = "";
		}

		$query = $items["query_path"];
		$items["queries"] = [];
		if( strlen($query) )
		{
			@ parse_str($query, $items["queries"]);
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
		return "context";
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
				"id", "name", "title", "description", "hostname", "host_port", "host_ssl", "path", "query_path", "default_context", "properties"
			]
		];
	}
}