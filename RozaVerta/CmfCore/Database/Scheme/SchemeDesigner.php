<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 18:17
 */

namespace RozaVerta\CmfCore\Database\Scheme;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;
use JsonSerializable;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Database\Interfaces\SchemeDesignerInterface;
use RozaVerta\CmfCore\Database\Query\Builder;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Traits\GetTrait;

/**
 * @example class name: TableName_SchemeDesigner or TableName_SchemeMode_SchemeDesigner
 *
 * Class SchemeDesigner
 * @package RozaVerta\CmfCore\Database\Scheme
 */
class SchemeDesigner implements SchemeDesignerInterface, Arrayable, JsonSerializable, VarExportInterface
{
	use GetTrait;

	/**
	 * @var array
	 */
	protected $items = [];

	public function __construct( array $items, ?Connection $connection = null )
	{
		$this->items = is_null($connection) ? $items : $this->format( $items, $connection->getDbalDatabasePlatform() );
	}

	protected function format( array $items, AbstractPlatform $platform ): array
	{
		return $items;
	}

	/**
	 * Specify data which should be serialized to JSON
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}

	public function getArrayForVarExport(): array
	{
		return [
			'items' => $this->items
		];
	}

	static public function __set_state( $data )
	{
		return new static( $data["items"] ?? [] );
	}

	/**
	 * Get table name
	 *
	 * @return string
	 */
	static public function getTableName(): string
	{
		throw new InvalidArgumentException("Dynamic SchemeDesigner, method must be overloaded");
	}

	/**
	 * Get schema for query builder
	 *
	 * @return array
	 */
	static public function getSchemaBuilder(): array
	{
		return [];
	}

	/**
	 * Create query builder for current table
	 *
	 * @param string|null $alias
	 * @param string|null $connection
	 * @return Builder
	 */
	static public function find( ? string $alias = null, ? string $connection = null ): Builder
	{
		return DatabaseManager::table( static::class, $alias, $connection );
	}
}