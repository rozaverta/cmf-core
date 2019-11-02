<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 20:38
 */

namespace RozaVerta\CmfCore\Database\Scheme;

use InvalidArgumentException;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Module\Module;
use RozaVerta\CmfCore\Module\ResourceJson;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Schemes\SchemeTables_SchemeDesigner;

class TableLoader extends TableDataLoader
{
	use ModuleGetterTrait;

	/**
	 * @var int
	 */
	protected $moduleId;

	/**
	 * @var ResourceJson
	 */
	protected $resource;

	private $addon = false;

	private $cacheVersion = null;

	/**
	 * TableLoader constructor.
	 *
	 * @param string               $name
	 * @param ModuleInterface|null $module
	 * @param string|null          $cacheVersion
	 *
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 * @throws \Throwable
	 */
	public function __construct( string $name, ?ModuleInterface $module = null, ?string $cacheVersion = null )
	{
		parent::__construct( $name );

		// find module in database scheme table
		if( !$module )
		{
			$moduleId = DatabaseManager::plainBuilder()
				->from( SchemeTables_SchemeDesigner::getTableName() )
				->where( "name", $name )
				->value( "module_id" );

			if( ! is_numeric($moduleId) )
			{
				throw new NotFoundException( "The \"{$name}\" table not found." );
			}

			$module = Module::module((int) $moduleId);
		}

		$resource = $module->getResourceJson("db_" . $name, $cacheVersion );
		if( $resource->getType() !== "#/database_table" )
		{
			throw new InvalidArgumentException( "Invalid resource file type for the \"{$name}\" table." );
		}

		$this->resource = $resource;

		$resource->isCacheVersion( $this->cacheVersion );
		$this->addon = $resource->isAddon();
		$this->setModule($module);

		$this->load(
			$resource->getArray( "columns" ),
			$resource->getArray( "indexes" ),
			$resource->has( "primaryKey" ) ? $resource->getArray( "primaryKey" ) : [],
			$resource->getArray( "foreignKeys" ),
			$resource->getArray( "options" ),
			$resource->getArray( "extra" )
		);
	}

	/**
	 * Table is addon
	 *
	 * @return bool
	 */
	public function isAddon(): bool
	{
		return $this->addon;
	}

	/**
	 * Table loaded from cache version
	 *
	 * @param null $cacheVersion
	 * @return bool
	 */
	public function isCacheVersion( & $cacheVersion = null ): bool
	{
		if( is_null( $this->cacheVersion ) )
		{
			return false;
		}
		else
		{
			$cacheVersion = $this->cacheVersion;
			return true;
		}
	}

	/**
	 * @return ResourceJson
	 */
	public function getResource(): ResourceJson
	{
		return $this->resource;
	}

	protected function createException( $text, $exception = InvalidArgumentException::class )
	{
		$text .= " Database resource config file \"db_{$this->name}.json\".";
		return new $exception($text);
	}
}