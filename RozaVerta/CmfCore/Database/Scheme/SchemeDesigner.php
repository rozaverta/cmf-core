<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 18:17
 */

namespace RozaVerta\CmfCore\Database\Scheme;

use BadMethodCallException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use InvalidArgumentException;
use JsonSerializable;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Database\Interfaces\ProxySchemeDesignerInterface;
use RozaVerta\CmfCore\Database\Interfaces\SchemeDesignerInterface;
use RozaVerta\CmfCore\Database\Query\SchemeDesignerFetchBuilder;
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

	/**
	 * @var ProxySchemeDesignerInterface[]
	 */
	private $proxy = [];

	/**
	 * @var int
	 */
	private $proxies = 0;

	public function __construct( array $items, ?Connection $connection = null )
	{
		$this->items = is_null($connection) ? $items : $this->format( $items, $connection->getDbalDatabasePlatform() );
	}

	protected function format( array $items, AbstractPlatform $platform ): array
	{
		return $items;
	}

	/**
	 * Add proxy object
	 *
	 * @param ProxySchemeDesignerInterface $object
	 *
	 * @return $this
	 */
	public function addProxy(ProxySchemeDesignerInterface $object)
	{
		$this->proxy[] = $object;
		$this->proxies ++;
		$this->items = array_merge($this->items, $object->toArray());
		return $this;
	}

	public function __call( $name, $arguments )
	{
		if( $this->proxies == 1 )
		{
			return $this->proxy->{$name}( ...$arguments );
		}

		if( $this->proxies > 1 )
		{
			for($i = 0; $i < $this->proxies; $i++)
			{
				if(method_exists($this->proxy[$i], $name))
				{
					return $this->proxy[$i]->{$name}(... $arguments);
				}
			}
		}

		throw new BadMethodCallException("Call to undefined method " . __CLASS__ . "::{$name}()");
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
			'items' => $this->items,
			'proxy' => $this->proxy
		];
	}

	static public function __set_state( $data )
	{
		$result = new static( $data["items"] ?? [] );
		if( !empty($data["proxy"]) && is_array($data["proxy"]) )
		{
			$result->proxy = $data["proxy"];
			$result->proxies = count($data["proxy"]);
		}
		return $result;
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
	 * @param string|null $connection
	 *
	 * @return SchemeDesignerFetchBuilder
	 *
	 * @throws \Throwable
	 */
	static public function find( ? string $connection = null ): SchemeDesignerFetchBuilder
	{
		return DatabaseManager::schemeDesignerFetchBuilder( static::class, $connection );
	}
}