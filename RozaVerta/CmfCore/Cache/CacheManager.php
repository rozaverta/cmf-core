<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:04
 */

namespace RozaVerta\CmfCore\Cache;

use RozaVerta\CmfCore\Cache\Apc\ApcStore;
use RozaVerta\CmfCore\Cache\Database\DatabaseStore;
use RozaVerta\CmfCore\Cache\File\FileStore;
use RozaVerta\CmfCore\Cache\Interfaces\CacheStoreInterface;
use RozaVerta\CmfCore\Cache\Memcached\MemcachedStore;
use RozaVerta\CmfCore\Cache\Redis\RedisStore;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Filesystem\Filesystem;
use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;
use Predis\Client;

class CacheManager
{
	use SingletonInstanceTrait;

	/**
	 * @var Prop
	 */
	protected $config;

	/**
	 * @var CacheStoreInterface[]
	 */
	protected $store = [];

	protected function __construct()
	{
		$config = Prop::prop("cache");

		if( !$config->isArray("default") )
		{
			$config->set("default", [
				"driver" => "file"
			]);
		}

		$this->config = $config;
	}

	/**
	 * Store was configured
	 *
	 * @param string $name
	 * @return bool
	 */
	public function hasStore(string $name): bool
	{
		return $this->config->isArray($name);
	}

	/**
	 * Get store
	 *
	 * @param string $name
	 * @return CacheStoreInterface
	 * @throws NotFoundException
	 */
	public function getStore( string $name = null ): CacheStoreInterface
	{
		if( is_null($name) )
		{
			$name = "default";
		}

		if( isset($this->store[$name]) || $this->loadStore($name) )
		{
			return $this->store[$name];
		}

		throw new NotFoundException("The '{$name}' cache store config not found");
	}

	/**
	 * Get cache config
	 *
	 * @return Prop
	 */
	public function getConfig(): Prop
	{
		return $this->config;
	}

	/**
	 * Create new Cache instance
	 *
	 * @param string $name
	 * @param string $prefix
	 * @param array $data
	 * @param string|null $store
	 * @return Cache
	 */
	public function newCache( string $name, string $prefix = "", array $data = [], string $store = null )
	{
		if( ! is_null($store) )
		{
			$store = $this->getStore($store);
		}

		return new Cache($name, $prefix, $data, $store);
	}

	/**
	 * Create an instance of the Memcached cache driver
	 *
	 * @param string $name
	 * @param Prop $config
	 * @return MemcachedStore
	 */
	protected function createMemcachedDriver(string $name, Prop $config)
	{
		$memcached = new \Memcached( $config->get( "persistent_id" ) );

		if($config->isArray("options"))
		{
			$memcached->setOptions($config->get("options"));
		}

		if( $config->has( "username" ) && method_exists( $memcached, "setSaslAuthData" ) )
		{
			$memcached->setSaslAuthData(
				$config->get("username"),
				$config->get( "password", "" )
			);
		}

		if($config->isArray("servers"))
		{
			$memcached->addServers($config->get("servers"));
		}
		else
		{
			$memcached->addServer(
				$config->get( "host", "127.0.0.1" ),
				$config->get( "port", 11211 ),
				$config->get( "weight", 0 )
			);
		}

		return new MemcachedStore(
			$memcached,
			$name,
			$config->get( "prefix", "" ),
			$config->get( "life", 0 )
		);
	}

	/**
	 * Create an instance of the Redis cache driver
	 *
	 * @param string $name
	 * @param Prop $config
	 * @return RedisStore
	 */
	protected function createRedisDriver(string $name, Prop $config)
	{
		if( !class_exists("Predis\\Client") )
		{
			throw new \RuntimeException("Redis client library is not loaded");
		}

		$parameters = $config->toArray();
		$options = $config->isArray("options") ? $config->get("options") : [];

		unset($parameters["options"]);

		if( ! isset($options["timeout"]) )
		{
			$options["timeout"] = 10;
		}

		if( isset($options["prefix"]) && strlen($options["prefix"]) )
		{
			$options["prefix"] = $options["prefix"] . ":";
		}

		$options["exceptions"] = true;

		return new RedisStore(
			new Client($config->toArray(), $options),
			$name,
			$config->get( "life", 0 )
		);
	}

	/**
	 * Create an instance of the database cache driver
	 *
	 * @param string $name
	 * @param Prop $config
	 * @return DatabaseStore
	 */
	protected function createDatabaseDriver(string $name, Prop $config)
	{
		return new DatabaseStore(
			App::getInstance()->database->getConnection( $config->get( "connection" ) ),
			$name,
			$config->get( "table", "cache" ),
			$config->get( "life", 0 )
		);
	}

	/**
	 * Create an instance of the APC cache driver
	 *
	 * @param string $name
	 * @param Prop $config
	 * @return ApcStore
	 */
	protected function createApcDriver(string $name, Prop $config)
	{
		if( ! function_exists("apcu_store") )
		{
			throw new \RuntimeException("APCu client library is not loaded");
		}

		$cli = function_exists("php_sapi_name") && strpos( php_sapi_name(), "cli") !== false;
		$enable = in_array(strtolower( ini_get("apc.enable" . ($cli ? "_cli" : "d")) ), ["on", "1"]);
		if( !$enable )
		{
			throw new \RuntimeException("APCu is not enabled, change php/apc ini configuration file");
		}

		return new ApcStore(
			$name,
			$config->get( "prefix", "" ),
			$config->get( "life", 0 )
		);
	}

	/**
	 * Create an instance of the file cache driver
	 *
	 * @param string $name
	 * @param Prop $config
	 * @return FileStore
	 */
	protected function createFileDriver(string $name, Prop $config)
	{
		return new FileStore(
			Filesystem::getInstance(),
			$name,
			$config->get( "directory", "cache" ),
			$config->get( "life", 0 )
		);
	}

	private function loadStore( string $name ): bool
	{
		if( !$this->hasStore($name) )
		{
			return false;
		}

		$config = $this->config->get($name);
		$driver = $config["driver"] ?? "file";
		$method = "create" . ucfirst($driver) . "Driver";

		if( method_exists($this, $method) )
		{
			$this->store[$name] = $this->{$method}($name, new Prop($config));
		}
		else
		{
			throw new \InvalidArgumentException("Invalid cache driver '{$driver}'");
		}

		return true;
	}
}