<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:26
 */

namespace RozaVerta\CmfCore\Cache;

use RozaVerta\CmfCore\Cache\Interfaces\CacheStoreInterface;

/**
 * Class Cache
 */
class Cache
{
	/**
	 * @var \RozaVerta\CmfCore\Cache\Interfaces\CacheDriverInterface
	 */
	private $factory;

	private static $manager = null;

	public function __construct( string $name, string $prefix = "", array $data = [], CacheStoreInterface $store = null )
	{
		$life = null;
		if( isset($data["life"]) && is_int($data["life"]) )
		{
			$life = $data["life"];
			unset($data["life"]);
		}

		if( is_null($store) )
		{
			$store = self::store();
		}

		$this->factory = $store->createFactory($name, $prefix, $data, $life);
	}

	/**
	 * @return CacheManager
	 */
	public static function manager(): CacheManager
	{
		if( ! isset(self::$manager) )
		{
			self::$manager = CacheManager::getInstance();
		}
		return self::$manager;
	}

	/**
	 * @param string|null $name
	 * @return \RozaVerta\CmfCore\Cache\Interfaces\CacheStoreInterface
	 */
	public static function store( string $name = null ): Interfaces\CacheStoreInterface
	{
		return self::manager()->getStore($name);
	}

	public function ready(): bool
	{
		return $this->factory->has();
	}

	public function set( string $value )
	{
		return $this->factory->set( $value );
	}

	public function get()
	{
		return $this->factory->get();
	}

	public function import()
	{
		return $this->factory->import();
	}

	public function export($data): bool
	{
		return $this->factory->export($data);
	}

	public function forget(): bool
	{
		return $this->factory->forget();
	}
}