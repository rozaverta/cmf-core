<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:17
 */

namespace RozaVerta\CmfCore\Cache\Database;

use RozaVerta\CmfCore\Cache\Interfaces\CacheDriverInterface;
use RozaVerta\CmfCore\Cache\DatabaseHash;
use RozaVerta\CmfCore\Cache\Properties\Property;
use RozaVerta\CmfCore\Cache\Properties\PropertyLog;
use RozaVerta\CmfCore\Cache\Properties\PropertyMemory;
use RozaVerta\CmfCore\Cache\Properties\PropertyStats;
use RozaVerta\CmfCore\Cache\Store;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Query\Builder;
use RozaVerta\CmfCore\Database\Query\Criteria;

class DatabaseStore extends Store
{
	use DatabaseConnectionTrait;

	public function __construct( Connection $connection, string $name, string $table = "cache", int $life = 0 )
	{
		parent::__construct($name, $life);
		$this->setConnection($connection, $table);
	}

	public function flush( string $prefix = null ): bool
	{
		$builder = $this->builder();

		if( is_null( $prefix ) )
		{
			return $this->fetch( function( Builder $builder ) {
				return $builder->delete() !== false;
			}, $builder );
		}

		$prefix = ( new DatabaseHash( "", $prefix ) )->keyPrefix();
		$builder
			->setWhereType( Criteria::TYPE_OR )
			->where( function( Criteria $criteria ) use ( $prefix ) {
				$criteria
					->eq( "prefix", $prefix )
					->like( "prefix", addcslashes( $prefix, "%_" ) . "%" );
			} );

		return $this->fetch( function( Builder $builder ) {
			return $builder->delete() !== false;
		}, $builder );
	}

	public function info(): array
	{
		$info = [];
		$info[] = new Property("driver", "database");
		$info[] = new Property("default_life", $this->life);

		$con = $this->getConnection();
		$info[] = new Property( "database_driver", $con->getDbalDatabasePlatform()->getName() );
		$info[] = new Property( "database_name", $con->getDatabase() );
		$info[] = new Property("cache_table", $this->getTable());

		return $info;
	}

	public function stats(): array
	{
		/** @var PropertyMemory[] $memories */
		$stats = [];
		$memories = [];

		$all = $this->fetch( function( Builder $builder ) {

			$grammar = $builder->getGrammar();

			return $builder
				->groupBy("prefix")
				->select( [
					"name",
					"prefix",
					$grammar->newExpression( 'COUNT(%s) as %s', [ 'id', 'items' ] ),
					$grammar->newExpression( 'SUM(%s) as %s', [ 'size', 'sizes' ] ),
				] )
				->get();

		}, $this->builder() );

		if( $all === false )
		{
			$stats[] = new PropertyLog("Error", "Database query failure");
		}
		else
		{
			$count = 0;
			$bytes = 0;
			$increment = 0;
			$keys = [];

			foreach($all as $item)
			{
				$key = empty( $item["prefix"] ) ? $item["name"] : $item["prefix"];
				$items = (int) $item["items"];
				$sizes = (int) $item["sizes"];

				$pos = strpos($key, "/", 1);
				if( $pos !== false )
				{
					$key = substr($key, 0, $pos);
				}

				$count += $items;
				$bytes += $sizes;

				if( ! isset($keys[$key]) )
				{
					$keys[$key] = $increment;
					$memories[$increment++] = new PropertyMemory($key, $sizes, $items);
				}
				else
				{
					$memories[$keys[$key]]->add($sizes);
				}
			}

			$stats = [
				new PropertyStats("items", $count),
				new PropertyStats("bytes", $bytes)
			];

			if(count($memories))
			{
				$stats = array_merge($stats, array_values($memories));
			}
		}

		return $stats;
	}

	public function createFactory( string $name, string $prefix = "", array $properties = [], int $life = null ): CacheDriverInterface
	{
		$value = new DatabaseDriver($this->connection, $this->table, new DatabaseHash($name, $prefix, $properties));
		$value->load(is_null($life) ? $this->life : $life);
		return $value;
	}
}