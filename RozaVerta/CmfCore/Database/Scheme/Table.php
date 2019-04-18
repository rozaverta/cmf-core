<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 19:41
 */

namespace RozaVerta\CmfCore\Database\Scheme;

use RozaVerta\CmfCore\Cache\CacheManager;
use RozaVerta\CmfCore\Support\Prop;

class Table
{
	use ExtraTrait;

	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string|null
	 */
	protected $idName = null;

	/**
	 * @var array
	 */
	protected $parentNames = [];

	/**
	 * @var Column[]
	 */
	protected $columns = [];

	/**
	 * @var array
	 */
	protected $columnIdn = [];

	/**
	 * Table constructor.
	 * @param string $name
	 * @param Column[] $columns
	 * @param Prop|null $extra
	 */
	public function __construct(string $name, array $columns, ?Prop $extra = null)
	{
		$this->name = $name;

		$uid = false;
		$ind = 0;
		foreach($columns as $column)
		{
			if($column instanceof Column)
			{
				$name = $column->getName();
				$this->columns[] = $column;
				$this->columnIdn[$name] = $ind ++;

				// ID column index name (primary key)
				if( ! $uid && $column->extra("isIdentifier") === true )
				{
					$uid = $name;
				}

				// this column is link for parent table ID index name
				$parentTable = $column->extra("parentTableName");
				if( $parentTable )
				{
					$this->parentNames[$parentTable] = $name;
				}
			}
		}

		if( $uid )
		{
			$this->idName = $uid;
		}
		else if( $this->exists("id") )
		{
			$this->idName = "id";
		}

		$this->extra = $extra === null ? new Prop() : $extra;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	public function getIdName(): ?string
	{
		return $this->idName;
	}

	public function getParentName( string $tableName ): ?string
	{
		return $this->parentNames[$tableName] ?? null;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function exists(string $name): bool
	{
		return isset($this->columnIdn[$name]);
	}

	/**
	 * @param string $name
	 * @return null|Column
	 */
	public function column(string $name): ?Column
	{
		return $this->exists($name) ? $this->columns[$this->columnIdn[$name]] : null;
	}

	/**
	 * @return Column[]
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}

	/**
	 * @return array
	 */
	public function getColumnNames(): array
	{
		return array_keys($this->columnIdn);
	}

	static private $tables = [];

	static public function table( string $name ): Table
	{
		if( ! isset(static::$tables[$name]) )
		{
			$cache = CacheManager::getInstance()->newCache($name, "database/scheme");
			if($cache->ready())
			{
				static::$tables[$name] = $cache->import();
			}
			else
			{
				$loader = new TableLoader($name);
				static::$tables[$name] = new Table(
					$loader->getName(), $loader->getColumns(), $loader->getExtras()
				);
				$cache->export(static::$tables[$name]);
			}
		}

		return static::$tables[$name];
	}
}