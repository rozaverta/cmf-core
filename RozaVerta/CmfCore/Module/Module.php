<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace RozaVerta\CmfCore\Module;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\DBAL\ParameterType;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Cache\Cache;
use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Module\Exceptions\ModuleConflictVersionException;
use RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Module\Interfaces\ModuleManifestInterface;
use RozaVerta\CmfCore\Manifest;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;

/**
 * Class Module
 *
 * @package RozaVerta\CmfCore\Module
 */
class Module extends Modular implements VarExportInterface, ModuleInterface
{
	use GetIdentifierTrait;

	/**
	 * @var bool
	 */
	protected $install;

	/**
	 * @var array
	 */
	static private $manifests = [];

	/**
	 * @var array
	 */
	static private $store = [];

	private function __construct( array $row )
	{
		$this->setId((int) $row["id"]);

		$this->name = $row["name"];
		$this->key = $row["key"];
		$this->front = $row["front"];
		$this->route = $row["route"];
		$this->title = $row["title"];
		$this->version = $row["version"];
		$this->pathname = $row["pathname"];
		$this->namespaceName = $row["namespaceName"];
		$this->support = $row["support"];
		$this->extra = $row["extra"];
		$this->install = $row["install"];
	}

	/**
	 * @return bool
	 */
	public function isInstall(): bool
	{
		return $this->install;
	}

	/**
	 * Get Manifest module object from local cache
	 *
	 * @return ModuleManifestInterface
	 */
	public function getManifest(): ModuleManifestInterface
	{
		$id = $this->getId();
		if( ! isset(self::$manifests[$id]) )
		{
			self::$manifests[$id] = $this->createManifest();
		}
		return self::$manifests[$id];
	}

	/**
	 * Create Manifest module object
	 *
	 * @return ModuleManifestInterface
	 */
	protected function createManifest(): ModuleManifestInterface
	{
		$className = $this->getNamespaceName() . "Manifest";
		return new $className();
	}

	/**
	 * Create new ResourceJson module object from resources/{$name}.json file
	 *
	 * @param string $name
	 * @param null|string $cacheVersion
	 *
	 * @return ResourceJson
	 *
	 * @throws Exceptions\ResourceNotFoundException
	 * @throws Exceptions\ResourceReadException
	 */
	public function getResourceJson( string $name, ?string $cacheVersion = null ): ResourceJson
	{
		return new ResourceJson( $name, $this, $cacheVersion );
	}

	/**
	 * Converting module master data to an array
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$array = parent::toArray();
		$array["id"] = $this->getId();
		$array["install"] = $this->isInstall();
		return $array;
	}

	/**
	 * Get Module object from local cache
	 *
	 * @param int $id
	 *
	 * @return Module
	 *
	 * @throws Exceptions\ResourceNotFoundException
	 * @throws Exceptions\ResourceReadException
	 * @throws ModuleNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static public function module( int $id ): ModuleInterface
	{
		if( ! isset(self::$store[$id]) )
		{
			$cache = new Cache($id, 'modules');
			if( $cache->ready() )
			{
				self::$store[$id] = $cache->import();
			}
			else
			{
				self::$store[$id] = self::create($id);
				$cache->export(self::$store[$id]);
			}
		}

		return self::$store[$id];
	}

	// -- protected

	/**
	 * @param int $id
	 * @param bool $install
	 *
	 * @return array
	 *
	 * @throws Exceptions\ResourceNotFoundException
	 * @throws Exceptions\ResourceReadException
	 * @throws ModuleNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static protected function load( int $id, bool $install = true )
	{
		$conn = DatabaseManager::connection();
		$query = "SELECT * FROM " . $conn->getTableName( Modules_SchemeDesigner::getTableName() ) . " WHERE id = ?";
		$where = [ $id ];
		$types = [ ParameterType::INTEGER ];

		if( $install )
		{
			$query .= " AND install = ?";
			$where[] = true;
			$types[] = ParameterType::BOOLEAN;
		}

		/** @var Modules_SchemeDesigner $row */
		try {
			$row = $conn->fetchAssoc(
				$conn->getGrammar()->compileLimitQuery( $query, 1 ), $where, $types
			);
			if( $row )
			{
				$row = new Modules_SchemeDesigner( $row, $conn );
			}
		}
		catch( TableNotFoundException $e ) {
			$row = false;
		}

		if( $row )
		{
			$manifest = $row->getManifest();
		}
		else if( $install || $id !== 1 )
		{
			throw new ModuleNotFoundException("The '{$id}' module not found");
		}
		else
		{
			$manifest = new Manifest();

			$row = new Modules_SchemeDesigner([
				"id" => 1,
				"name" => $manifest->getName(),
				"install" => false,
				"namespace_name" => $manifest->getNamespaceName(),
				"version" => $manifest->getVersion()
			], $conn );
		}

		if( $install )
		{
			if( $manifest->getVersion() !== $row->getVersion() )
			{
				throw new ModuleConflictVersionException("The current version of the '{$row->get('name')}' module does not match the installed version of the module");
			}

			if( ! isset(self::$manifests[$id]) )
			{
				self::$manifests[$id] = $manifest;
			}
		}

		$get = [
			'id' => $id,
			'name' => $manifest->getName(),
			'key' => $manifest->getKey(),
			'route' => $manifest->isRoute(),
			'title' => $manifest->getTitle(),
			'front' => $manifest->isFront(),
			'version' => $row->getVersion(),
			'install' => $row->isInstall(),
			'pathname' => $manifest->getPathname(),
			'namespaceName' => $manifest->getNamespaceName(),
			'support' => $manifest->getSupport(),
			'extra' => $manifest->getExtras()
		];

		return $get;
	}

	/**
	 * @param int $id
	 * @param bool $install
	 *
	 * @return Module
	 *
	 * @throws Exceptions\ResourceNotFoundException
	 * @throws Exceptions\ResourceReadException
	 * @throws ModuleNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	static protected function create( int $id, bool $install = true )
	{
		return new static( self::load( $id, $install ) );
	}

	private function __clone() {}

	public function getArrayForVarExport(): array
	{
		return $this->toArray();
	}

	static public function __set_state( $data )
	{
		$id = $data["id"];
		return isset(self::$store[$id]) ? self::$store[$id] : new Module( $data );
	}
}