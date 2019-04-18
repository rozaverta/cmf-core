<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.03.2019
 * Time: 3:45
 */

namespace RozaVerta\CmfCore\Workshops\View;

use RozaVerta\CmfCore\Module\ModuleHelper;
use RozaVerta\CmfCore\Schemes\TemplatePackages_SchemeDesigner;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Workshops\View\Exceptions\PackageNotFoundException;

class PackageProcessor extends Workshop
{
	/**
	 * PackageProcessor constructor.
	 *
	 * @param string $packageName
	 *
	 * @throws PackageNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function __construct( string $packageName )
	{
		$moduleId = $this
			->db
			->table(TemplatePackages_SchemeDesigner::getTableName())
			->where("name", $packageName)
			->value("module_id");

		if( ! is_numeric($moduleId) )
		{
			throw new PackageNotFoundException("The '{$packageName}' template package not found");
		}

		parent::__construct( ModuleHelper::workshop($moduleId) );
	}

	public function update(array $manifest)
	{
		//
	}

	public function addTemplate(string $templateName, string $content = "")
	{
		//
	}

	public function removeTemplate(string $templateName)
	{
		//
	}

	public function getTemplate(string $templateName): TemplateProcessor
	{
		//
	}

	public function uploadAssetsFile(string $fileName, $file)
	{
		//
	}

	public function removeAssetsFile(string $fileName)
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