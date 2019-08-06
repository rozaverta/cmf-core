<?php

/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2015
 * Time: 0:52
 */

namespace RozaVerta\CmfCore\View;

use RozaVerta\CmfCore\Cache\Cache;
use RozaVerta\CmfCore\Cache\CacheManager;
use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Helper\Json;
use RozaVerta\CmfCore\Helper\Str;
use RozaVerta\CmfCore\Traits\ComparatorTrait;
use RozaVerta\CmfCore\Traits\GetTrait;
use RozaVerta\CmfCore\View\Interfaces\PluginDynamicInterface;

abstract class PluginDynamic extends Plugin implements PluginDynamicInterface
{
	use GetTrait;
	use ComparatorTrait;

	public const CACHE_NOCACHE  = 1;
	public const CACHE_DATA     = 2;
	public const CACHE_PLUGIN   = 3;
	public const CACHE_PAGE     = 4;

	protected static $cacheText = [
		"NOCACHE"   => self::CACHE_NOCACHE,
		"DATA"      => self::CACHE_DATA,
		"PLUGIN"    => self::CACHE_PLUGIN,
		"PAGE"      => self::CACHE_PAGE
	];

	private $items = [];

	private $pluginData = [];

	/**
	 * @values self::CACHE_NOCACHE | self::CACHE_DATA | self::CACHE_PLUGIN | self::CACHE_PAGE
	 *
	 * @var int
	 */
	private $cacheType = self::CACHE_PAGE;

	private $cacheData = [];

	/**
	 * @var null|string
	 */
	protected $pluginCache = null;

	public function ready( array $data = [] )
	{
		if( isset( $data["cache"] ) )
		{
			$cacheType = $data["cache"];
			if( is_string($cacheType) )
			{
				$cacheType = strtoupper( trim( $cacheType ) );
				if( isset(self::$cacheText[$cacheType]) )
				{
					$cacheType = self::$cacheText[$cacheType];
				}
			}

			if( $cacheType === self::CACHE_NOCACHE || $cacheType === self::CACHE_DATA || self::CACHE_PLUGIN )
			{
				$this->cacheType = $cacheType;
			}
		}

		if( isset($data["cacheData"]) )
		{
			$cacheData = $data["cacheData"];
			if( is_string($cacheData) && ltrim($cacheData)[0] === '{' )
			{
				// read as JSON
				$cacheData = ltrim($cacheData);
				if( strlen($cacheData) && $cacheData[0] === '{' )
				{
					$this->cacheData = Json::getArrayProperties($cacheData);
				}
			}
			else if( is_array($cacheData) )
			{
				$this->cacheData = $cacheData;
			}

			// change array to assoc
			// [name, value] to [name => value]
			$this->cacheData = $this->arrayToAssocCache($this->cacheData);
		}

		unset( $data["cache"], $data["cacheData"] );

		$this->items = $data;

		if($this->cacheType === self::CACHE_DATA)
		{
			$this->readFromDataCache();
		}
		else if($this->cacheType === self::CACHE_PLUGIN)
		{
			$this->readFromPluginCache();
		}
		else
		{
			$this->complete();
		}

		return $this;
	}

	/**
	 * Get cache type.
	 * Valid values: nocache, data, view, plugin
	 *
	 * @return string
	 */
	public function getCacheType(): string
	{
		return $this->cacheType;
	}

	/**
	 * Get full cache data
	 *
	 * @return array
	 */
	public function getCacheData(): array
	{
		return $this->cacheData;
	}

	// protected

	/**
	 * @return array
	 */
	public function getPluginData(): array
	{
		return $this->pluginData;
	}

	public function render(): string
	{
		if( $this->pluginCache !== null )
		{
			return $this->pluginCache;
		}

		if( $this->lexer instanceof View )
		{
			return $this->lexer->getChunk( $this->getChunkName(), $this->pluginData );
		}

		throw new \RuntimeException("You must overloaded the " . __METHOD__ . " method for the CUSTOM_DISPLAY mode");
	}

	// protected

	protected function getChunkName(): string
	{
		$chunk = $this->get("chunk");

		if( $chunk && ! is_string($chunk) )
		{
			if( is_object($chunk) && method_exists($chunk, '__toString') )
			{
				$chunk = (string) $chunk;
			}
		}
		else if( ! $chunk )
		{
			// auto dir: /plugin/module_key_name/plugin_name.php
			$chunk = "plugin." . $this->getModule()->getKey() . "." . Str::snake(static::getPluginName());
			$this->items["chunk"] = $chunk;
		}

		return $chunk;
	}

	private function newCache(string $type): Cache
	{
		$mgr = CacheManager::getInstance();
		$store = null;
		$prefix = $type . $this->getModule()->getKey();

		if($mgr->hasStore("plugins"))
		{
			$store = "plugins";
		}
		else
		{
			$prefix = "plugins/" . $prefix;
		}

		return $mgr->newCache(
			Str::snake(static::getPluginName()),
			$prefix,
			$this->getCacheData(),
			$store
		);
	}

	private function readFromDataCache()
	{
		$cache = $this->newCache("data_cache");

		if( $cache->ready() )
		{
			$this->pluginData = $cache->import();
		}
		else
		{
			$cache->export($this->complete()->getPluginData());
		}
	}

	private function readFromPluginCache()
	{
		$cache = $this->newCache("text_cache");

		if( $cache->ready() )
		{
			$this->pluginCache = (string) $cache->get();
		}
		else
		{
			$this->pluginCache = $this->complete()->render();
			$cache->set($this->pluginCache);
		}
	}

	private function arrayToAssocCache(array $data): array
	{
		$cnt = count($data);

		if( $cnt > 1 && $cnt % 2 === 0 && key($data) === 0 && ! Arr::associative($data) )
		{
			$assoc = [];
			for($i = 0; $i < $cnt; $i += 2)
			{
				$key = $data[$i];
				if((is_string($key) || is_int($key)) && ! array_keys($data, $key))
				{
					$assoc[$key] = $data[$i + 1];
				}
				else
				{
					return $data;
				}
			}

			$this->cacheData = $assoc;
		}

		return $data;
	}
}