<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.03.2019
 * Time: 13:42
 */

namespace RozaVerta\CmfCore\View\Workshop;

use RozaVerta\CmfCore\Support\Workshop;

class PackageManager extends Workshop
{
	public function install( string $packageName )
	{
		//
	}

	public function update( string $packageName, bool $force = false )
	{
		//
	}

	public function uninstall( string $packageName )
	{
		//
	}

	public function create( string $packageName, array $manifest )
	{
		//
	}

	public function getPackage( string $packageName ): PackageProcessor
	{
		//
	}

	private function unZip(string $name, bool $update = false): Prop
	{
		$file = $this->getModule()->getPathname() . "resources" . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR . $name . ".zip";
		if(!file_exists($file))
		{
			throw new Exceptions\PackageNotFoundException("PackageManagerProcessor zip file '{$name}.zip' not found");
		}

		$fs = $this->filesystem;
		$viewDir = Path::view($name);
		$assetsDir = Path::assets($name);

		if( !$update )
		{
			if($fs->exists($viewDir))
			{
				throw new Exceptions\PackageInvalidArgumentsException("Warning! The '{$name}' view directory already exists");
			}
			if($fs->exists($assetsDir))
			{
				throw new Exceptions\PackageInvalidArgumentsException("Warning! The '{$name}' assets directory already exists");
			}
		}

		$zip = new ZipArchive();
		if( !$zip->open($file) )
		{
			throw new RuntimeException("Could not open zip file");
		}

		$tmp = sys_get_temp_dir();
		$tmpZipDir = $tmp . DIRECTORY_SEPARATOR . md5("id-" . $this->getModuleId() . "-" . $name . "-" . time());

		if( !$tmp || !$fs->isWritable($tmp) || !$fs->makeDirectory($tmpZipDir) )
		{
			throw new RuntimeException("Unable to unpack zip package file, tmp directory is not writable");
		}

		if( !$zip->extractTo($tmpZipDir) )
		{
			throw new RuntimeException("Unable to extract zip package file");
		}
		else
		{
			$zip->close();
		}

		$manifest = $tmpZipDir . DIRECTORY_SEPARATOR . "manifest.json";
		if( !file_exists($manifest) )
		{
			throw new Exceptions\PackageInvalidArgumentsException("");
		}

		$manifestText = file_get_contents($manifest);
		if( !$manifestText )
		{
			throw new Exceptions\PackageInvalidArgumentsException("");
		}

		try {
			$manifestJson = Json::getArrayProperties($manifestText, true);
		}
		catch(JsonParseException $e) {
			throw new Exceptions\PackageInvalidArgumentsException("", $e);
		}

		if( isset($manifestJson["name"]) && $manifestJson["name"] !== $name )
		{
			throw new Exceptions\PackageInvalidArgumentsException("");
		}

		$prop = new Prop($manifestJson);
		$prop->set(compact('assetsDir', 'viewDir', 'tmpZipDir'));
		return $prop;
	}
}