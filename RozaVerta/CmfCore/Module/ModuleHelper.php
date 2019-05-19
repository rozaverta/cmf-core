<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2018
 * Time: 16:48
 */

namespace RozaVerta\CmfCore\Module;

use Doctrine\DBAL\Exception\TableNotFoundException;
use RozaVerta\CmfCore\Cache\CacheManager;
use RozaVerta\CmfCore\Exceptions\RuntimeException;
use RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Database\DatabaseManager as DB;
use RozaVerta\CmfCore\Helper\Str;

/**
 * Class ModuleHelper
 *
 * @package RozaVerta\CmfCore\Module
 */
final class ModuleHelper
{
	private function __construct() {}

	/**
	 * Validate the module name
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	static public function validName(string $name): bool
	{
		$len = strlen($name);
		return $len > 0
			&& $len < 51
			&& ctype_upper($name[0])
			&& ! preg_match('/[^a-zA-Z0-9]/', $name);
	}

	/**
	 * Validate the module key (short name)
	 *
	 * @param string $key
	 *
	 * @return bool
	 */
	static public function validKey(string $key): bool
	{
		$len = strlen($key);
		if( $len < 1 )
		{
			return false;
		}

		// convert "my-key" to "my_key"
		// but that will not work for "my-key_variant"
		if( strpos($key, "-") !== false && strpos($key, "_") === false )
		{
			$key = str_replace("_", "-", $key);
		}

		return ctype_lower($key[0])
			&& ! preg_match('/[^a-z0-9_]/', $key)
			&& strpos("__", $key) === false
			&& $key[$len - 1] !== "_"
			&& strlen( str_replace("_", "", $key) ) < 51;
	}

	/**
	 * Format and validate module name
	 *
	 * @param string $name
	 *
	 * @return null|string
	 */
	static public function toNameStrict( string $name ): ?string
	{
		$len = strlen($name);
		if( $len < 1 )
		{
			return null;
		}

		$name = str_replace("-", "_", $name);
		if(strpos($name, "__") !== false || $name[0] === "_" || $len > 1 && $name[$len-1] === "_")
		{
			return null;
		}

		if( strpos($name, "_") !== false || ! ctype_upper($name[0]) )
		{
			$name = self::toName($name);
		}

		if( strlen($name) > 50 || is_numeric($name[0]) || preg_match('/[^a-zA-Z0-9]/', $name ) )
		{
			return null;
		}

		return $name;
	}

	/**
	 * Convert key to module name
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	static public function toKey(string $name): string
	{
		return Str::snake($name);
	}

	/**
	 * Convert module name to key
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	static public function toName(string $key): string
	{
		return Str::studly($key);
	}

	/**
	 * Has module install
	 *
	 * @param string $name
	 * @param null $moduleId
	 *
	 * @return bool
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function installed( string $name, & $moduleId = null ): bool
	{
		$id = self::idn($name);
		if( $id > 0 )
		{
			$moduleId = $id;
			return true;
		}
		return false;
	}

	/**
	 * Check the selected modules are installed and throw new RuntimeException if not yet
	 *
	 * @param array $modules
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function dependenceStrict( array $modules )
	{
		foreach($modules as $name)
		{
			$name = (string) $name;
			if( ! self::installed($name) )
			{
				throw new RuntimeException("Dependency error: the '" . self::toName($name) . "' module is not installed");
			}
		}
	}

	/**
	 * Module exists
	 *
	 * @param string $name
	 * @param null $moduleId
	 *
	 * @return bool
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function exists( string $name, & $moduleId = null ): bool
	{
		try {
			$builder = DB::table(Modules_SchemeDesigner::getTableName())->select(["id"]);

			if( is_numeric($name) )
			{
				$builder->whereId( (int) $name );
			}
			else
			{
				$builder->where("name", self::toName( (string) $name ) );
			}

			$id = $builder->value();
		}
		catch(TableNotFoundException $e) {
			return false;
		}

		if( is_numeric($id) && $id > 0 )
		{
			$moduleId = $id;
			return true;
		}

		return false;
	}

	/**
	 * Get module id from name or key
	 *
	 * @param string $name
	 *
	 * @return int|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function getId( string $name ): ?int
	{
		$id = self::idn($name);
		return $id > 0 ? $id : null;
	}

	/**
	 * Get workshop module processor. Read data from database
	 *
	 * @param string|int $name
	 *
	 * @return WorkshopModuleProcessor
	 *
	 * @throws Exceptions\ResourceNotFoundException
	 * @throws Exceptions\ResourceReadException
	 * @throws ModuleNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function workshop( $name ): WorkshopModuleProcessor
	{
		$builder = DB::table(Modules_SchemeDesigner::getTableName())->select(["id"]);

		if( is_numeric($name) )
		{
			$builder->whereId( (int) $name );
		}
		else
		{
			$builder->where("name", self::toName( (string) $name ) );
		}

		try {
			$id = $builder->value();
		}
		catch(TableNotFoundException $e) {
			$id = false;
		}

		if( ! is_numeric($id) )
		{
			throw new ModuleNotFoundException("The '{$name}' module not found");
		}

		/** @noinspection PhpIncompatibleReturnTypeInspection */
		return WorkshopModuleProcessor::module((int) $id);
	}

	/**
	 * Get module. Cached. Only installed module
	 *
	 * @param string|int $name
	 *
	 * @return Module
	 *
	 * @throws Exceptions\ResourceNotFoundException
	 * @throws Exceptions\ResourceReadException
	 * @throws ModuleNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function module( $name ): Module
	{
		$id = self::idn($name);

		if( $id > 0 )
		{
			return Module::module($id);
		}

		throw new ModuleNotFoundException("The '{$name}' module not found");
	}

	/**
	 * Get module namespace name. Cached. Only installed module
	 *
	 * @param int|string $name
	 *
	 * @return string|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function getNamespaceName( $name ): ?string
	{
		return self::idv( $name, "ns" );
	}

	/**
	 * Get module name by ID. Cached. Only installed module
	 *
	 * @param int $id
	 *
	 * @return string|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function getName( int $id )
	{
		return self::idv( $id, "name" );
	}

	/**
	 * Get module key by ID. Cached. Only installed module
	 *
	 * @param int $id
	 *
	 * @return string|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function getKey( int $id )
	{
		return self::idv( $id, "key" );
	}

	/**
	 * Get module scheme designer database record
	 *
	 * @param $name
	 *
	 * @return Modules_SchemeDesigner
	 * @throws ModuleNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function getSchemeDesigner( $name ): Modules_SchemeDesigner
	{
		$builder = DB::table(Modules_SchemeDesigner::class );

		if( is_numeric($name) )
		{
			$builder->whereId((int) $name);
		}
		else
		{
			$builder->where("name", self::toName( (string) $name ));
		}

		/** @var Modules_SchemeDesigner|false $row */
		$row = $builder->first();
		if( !$row )
		{
			throw new ModuleNotFoundException("The '{$name}' module not found");
		}

		return $row;
	}

	/**
	 * Get module property by ID from cache
	 *
	 * @param $name
	 * @param string $property
	 *
	 * @return string|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static protected function idv( $name, string $property ): ?string
	{
		static $store;

		if( ! is_int($name) )
		{
			$id = self::idn($name);
			if( $id < 1 )
			{
				return null;
			}
		}

		if( ! isset($store) )
		{
			$cache = CacheManager::getInstance()->newCache("ns", "modules");
			if($cache->ready())
			{
				$store = $cache->import();
			}
			else
			{
				/** @var Modules_SchemeDesigner[] $all */
				$all = DB::table(Modules_SchemeDesigner::class)
					->where("install", true)
					->get();

				$store = [];

				foreach($all as $item)
				{
					$store[$item->getId()] = [
						"ns"   => $item->getNamespaceName(),
						"name" => $item->getName(),
						"key"  => $item->getKey()
					];
				}

				if(count($store))
				{
					$cache->export($store);
				}
			}
		}

		return isset($store[$id]) ? $store[$id][$property] : null;
	}

	/**
	 * Get module id from cache
	 *
	 * @param $name
	 *
	 * @return int
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static protected function idn( $name ): int
	{
		static $idn;

		if( ! isset($idn) )
		{
			$cache = CacheManager::getInstance()->newCache("idn", "modules");
			if($cache->ready())
			{
				$idn = $cache->import();
			}
			else
			{
				$all = DB::table(Modules_SchemeDesigner::getTableName())
					->where("install", true)
					->select(["id", "name", "namespace_name"])
					->project(function($item) { $item["id"] = (int) $item["id"]; return $item; });

				$idn = [];

				foreach($all as $item)
				{
					// Add link rules for variants:
					// [ModuleName]  = INT
					// [module_name] = INT
					// [module-name] = INT
					// [INT] = INT
					// [:NameSpace\Module] = INT

					$idn[$item["id"]] = $item["id"];
					$idn[$item["name"]] = $item["id"];
					$idn[":" . trim($item["namespace_name"], '\\')] = $item["id"];
					$key = self::toKey($item["name"]);
					$idn[$key] = $item["id"];
					if( strpos($key, "_") !== false )
					{
						$idn[str_replace("_", "-", $key)] = $item["id"];
					}
				}

				if(count($idn))
				{
					$cache->export($idn);
				}
			}
		}

		if( is_numeric($name) )
		{
			return $idn[(int) $name] ?? 0;
		}

		if( isset($idn[$name]) )
		{
			return $idn[$name];
		}

		$name = ":" . trim($name, "\\");
		while(true)
		{
			if( isset($idn[$name]) )
			{
				return $idn[$name];
			}

			$end = strrpos($name, "\\");
			if( $end === false )
			{
				break;
			}

			$name = substr($name, 0, $end);
		}

		return 0;
	}
}