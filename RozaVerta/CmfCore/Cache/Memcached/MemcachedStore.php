<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:16
 */

namespace RozaVerta\CmfCore\Cache\Memcached;

use InvalidArgumentException;
use RozaVerta\CmfCore\Cache\Interfaces\CacheDriverInterface;
use RozaVerta\CmfCore\Cache\DatabaseHash;
use RozaVerta\CmfCore\Cache\Properties\Property;
use RozaVerta\CmfCore\Cache\Properties\PropertyStats;
use RozaVerta\CmfCore\Cache\Store;
use Memcached;

class MemcachedStore extends Store
{
	use MemcachedConnectionTrait;

	protected $prefix;

	public function __construct( Memcached $connection, string $store_name, string $prefix = "", int $life = 0 )
	{
		parent::__construct($store_name, $life);
		$this->setConnection($connection);
		$this->prefix = $prefix;
	}

	public function createFactory( string $name, string $prefix = "", array $properties = [], int $life = null ): CacheDriverInterface
	{
		$value = new MemcachedDriver($this->getConnection(), new DatabaseHash($name, $this->prefix . $prefix, $properties));
		$value->load(is_null($life) ? $this->life : $life);
		return $value;
	}

	public function flush( string $prefix = null ): bool
	{
		if( ! is_null($prefix) )
		{
			throw new InvalidArgumentException("Prefix flush is not support for Memcached driver");
		}

		return $this->result(
			$this->getConnection()->flush()
		);
	}

	public function info(): array
	{
		$info = [];

		$info[] = new Property("driver", "memcache");
		$info[] = new Property("driver_version", $this->getConnection()->getVersion());
		$info[] = new Property("default_life", $this->life);

		return $info;
	}

	public function stats(): array
	{
		$memcached = $this->getConnection();
		$all = $memcached->getStats();
		$stats = [];

		if(is_array($all) && count($all) > 0)
		{
			foreach($all as $server => $info)
			{
				$stats[] = new Property("server_connection", $server);
				foreach($info as $name => $value)
				{
					$stats[] = new PropertyStats($name, $value);
				}
			}
		}

		return $stats;
	}
}