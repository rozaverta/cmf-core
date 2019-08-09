<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.03.2019
 * Time: 3:45
 */

namespace RozaVerta\CmfCore\Workshops\View;

use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Module\ModuleHelper;
use RozaVerta\CmfCore\Schemes\TemplatePackages_SchemeDesigner;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Workshops\View\Exceptions\PackageNotFoundException;

class PackageProcessor extends Workshop
{
	/**
	 * PackageProcessor constructor.
	 *
	 * @param string $name
	 *
	 * @throws PackageNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 * @throws \Throwable
	 */
	public function __construct( string $name )
	{
		$moduleId = DatabaseManager::plainBuilder()
			->from( TemplatePackages_SchemeDesigner::getTableName() )
			->where( "name", $name )
			->value("module_id");

		if( ! is_numeric($moduleId) )
		{
			throw new PackageNotFoundException( "The \"{$name}\" template package not found." );
		}

		parent::__construct( ModuleHelper::workshop($moduleId) );
	}

	public function update( array $manifest)
	{
		//
	}

	public function addTemplate( string $templateName, string $content = "")
	{
		//
	}

	public function removeTemplate( string $templateName)
	{
		//
	}

	public function getTemplate( string $templateName): TemplateProcessor
	{
		//
	}

	public function uploadAssetsFile( string $fileName, $file)
	{
		//
	}

	public function removeAssetsFile( string $fileName)
	{
		//
	}

	public function updateFunctionalFile(string $text)
	{
		//
	}

	public function removeFunctionalFile()
	{
		//
	}
}