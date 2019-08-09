<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.08.2019
 * Time: 14:50
 */

namespace RozaVerta\CmfCore\View\Helpers;

use RozaVerta\CmfCore\Cache\Cache;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Schemes\TemplatePackages_SchemeDesigner;

/**
 * Class PackageHelper
 *
 * @package RozaVerta\CmfCore\View\Helpers
 */
final class PackageHelper
{
	private function __construct()
	{
	}

	/**
	 * Check package exists.
	 *
	 * @param string $name
	 * @param null   $id
	 *
	 * @return bool
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 */
	public static function exists( string $name, & $id = null ): bool
	{
		$value = DatabaseManager::plainBuilder()
			->from( TemplatePackages_SchemeDesigner::getTableName() )
			->where( "name", $name )
			->value( "id" );

		if( is_numeric( $value ) )
		{
			$id = (int) $value;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get template ID from name.
	 *
	 * @param string $name
	 *
	 * @return int|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 */
	public static function getIdFromName( string $name ): ?int
	{
		$value = DatabaseManager::plainBuilder()
			->from( TemplatePackages_SchemeDesigner::getTableName() )
			->where( "name", $name )
			->value( "id" );

		return is_numeric( $value ) ? (int) $value : null;
	}

	/**
	 * Get template name from ID.
	 *
	 * @param int $id
	 *
	 * @return string|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 */
	public static function getNameFromId( int $id ): ?string
	{
		$value = DatabaseManager::plainBuilder()
			->from( TemplatePackages_SchemeDesigner::getTableName() )
			->where( "id", $id )
			->value( "name" );

		return is_string( $value ) ? $value : null;
	}

	/**
	 * @param string $name
	 *
	 * @return int|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	static public function getIdFromNameCached( string $name ): ?int
	{
		static $idn = null;

		if( is_null( $idn ) )
		{
			// load packages IDs
			$cache = new Cache( 'id_from_name', 'template/package' );
			if( $cache->ready() )
			{
				$idn = $cache->import();
			}
			else
			{
				$all = TemplatePackages_SchemeDesigner::find()
					->orderBy( "name" )
					->get();

				/** @var TemplatePackages_SchemeDesigner $item */
				foreach( $all as $item )
				{
					$idn[$item->getName()] = $item->getId();
				}

				$cache->export( $idn );
			}
		}

		return $idn[$name] ?? null;
	}
}