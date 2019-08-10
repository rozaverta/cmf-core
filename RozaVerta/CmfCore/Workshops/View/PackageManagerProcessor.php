<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.03.2019
 * Time: 2:01
 */

namespace RozaVerta\CmfCore\Workshops\View;

use Doctrine\DBAL\DBALException;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Filesystem\Iterator;
use RozaVerta\CmfCore\Filesystem\Traits\WriteFileTrait;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Module\Exceptions\ExpectedModuleException;
use RozaVerta\CmfCore\Module\ModuleHelper;
use RozaVerta\CmfCore\Module\ResourceJson;
use RozaVerta\CmfCore\Schemes\TemplatePackages_SchemeDesigner;
use RozaVerta\CmfCore\Support\Collection;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Support\Text;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\View\Helpers\PackageHelper;
use RozaVerta\CmfCore\Workshops\Helper\LastInsertIdTrait;
use RozaVerta\CmfCore\Workshops\View\Exceptions\PackageNotFoundException;
use ZipArchive;

/**
 * Class PackageManagerProcessor
 *
 * @package RozaVerta\CmfCore\Workshops\View
 */
class PackageManagerProcessor extends Workshop
{
	use LastInsertIdTrait;
	use WriteFileTrait;

	public const PACKAGE_SYSTEM = 1;
	public const PACKAGE_ADDON = 2;
	public const PACKAGE_ALL = 3;

	/**
	 * Get module packages.
	 *
	 * @param int $mode
	 *
	 * @return Collection | TemplatePackages_SchemeDesigner[]
	 *
	 * @throws DBALException
	 */
	public function getPackages( int $mode = self::PACKAGE_ALL ): Collection
	{
		$builder = $this
			->db
			->plainBuilder()
			->from( TemplatePackages_SchemeDesigner::getTableName() )
			->where( "module_id", $this->getModuleId() );

		if( $mode === self::PACKAGE_SYSTEM )
		{
			$builder->where( "addon", false );
		}
		else if( $mode === self::PACKAGE_ADDON )
		{
			$builder->where( "addon", true );
		}
		else if( $mode !== self::PACKAGE_ALL )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "Invalid package mode." );
		}

		$all = $builder->project( function( array $row ) {
			return new TemplatePackages_SchemeDesigner( $row, $this->db );
		} );

		return new Collection( $all );
	}

	/**
	 * Install the package from the zip archive.
	 *
	 * @param string $name
	 *
	 * @return $this
	 *
	 * @throws DBALException
	 * @throws Exceptions\PackageNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \Throwable
	 */
	public function install( string $name )
	{
		$this->clearLastInsertId();
		if( !ModuleHelper::validKey( $name ) )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "Invalid package name \"{$name}\"." );
		}

		$name = str_replace( "_", "-", $name );
		if( PackageHelper::exists( $name ) )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "The \"{$name}\" package already exists." );
		}

		$manifest = $this->unZip( $name );

		// dispatch event

		$event = new Events\InstallPackageEvent( $this, $name, $manifest );
		$dispatcher = $this->event->dispatcher( $event->getName() );
		$dispatcher->dispatch( $event );

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Package installation aborted." );
		}

		// Copy data from zip file

		$this->createPackageFromZip( null, $manifest, function( $exception ) use ( $manifest ) {
			$dirs = $manifest->get( "__dirs" );
			$this->filesystem->deleteDirectory( $dirs["view"], true );
			$this->filesystem->deleteDirectory( $dirs["assets"], true );
			return $exception;
		} );

		$dispatcher->complete( $this->getLastInsertId() );
		$this->addDebug( Text::text( "The \"%s\" package was successfully installed.", $name ) );

		return $this;
	}

	/**
	 * Update the package from the zip archive.
	 *
	 * @param string $name
	 * @param bool   $force
	 *
	 * @return $this
	 *
	 * @throws DBALException
	 * @throws PackageNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 * @throws \Throwable
	 */
	public function update( string $name, bool $force = false )
	{
		$this->clearLastInsertId();
		try
		{
			$package = $this->package( $name );
		} catch( PackageNotFoundException $e )
		{
			return $this->install( $name );
		}

		if( $package->isAddon() )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "The specified \"{$name}\" package belongs to another module." );
		}

		$package->getVersion();
		$manifest = $this->unZip( $name, true );
		$compare = version_compare( $package->getVersion(), $manifest->getOr( "version", "1.0" ), ">" );
		if( $compare < 1 )
		{
			if( $compare < 0 )
			{
				throw new Exceptions\PackageInvalidArgumentsException( "Version error. The installed version is higher than the \"{$name}\" package version." );
			}
			else if( !$force )
			{
				return $this;
			}
		}

		// dispatch event

		$event = new Events\UpdatePackageEvent( $this, $name, $manifest, $package, $force );
		$dispatcher = $this->event->dispatcher( $event->getName() );
		$dispatcher->dispatch( $event );

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Package update aborted." );
		}

		$file = $this->zip( $name, $package->getVersion() );
		$size = filesize( $file );

		$this->addDebug( "Created backup file \"{$file}\" for the \"{$name}\" package, \"{$size}\" byte(s)." );

		$dirs = $manifest->get( "__dirs" );
		$this->filesystem->deleteDirectory( $dirs["view"] );
		$this->filesystem->deleteDirectory( $dirs["assets"] );

		$this->createPackageFromZip( $package->getId(), $manifest, function( $exception ) {
			return $exception;
		} );
		$dispatcher->complete( $package->getId() );
		$this->addDebug( Text::text( "The \"%s\" package was successfully updated.", $name ) );

		return $this;
	}

	/**
	 * Uninstall package.
	 *
	 * @param string $name
	 *
	 * @return $this
	 *
	 * @throws DBALException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 * @throws \Throwable
	 */
	public function uninstall( string $name )
	{
		$this->clearLastInsertId();
		try
		{
			$package = $this->package( $name );
		} catch( PackageNotFoundException $e )
		{
			$this->addError( $e->getMessage() );
			return $this;
		}

		$manifest = new Prop( ResourceJson::pathToJson( Path::view( $name . "/manifest.json" ) ) );
		if( $manifest->get( "name" ) !== $name )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "The package name does not match the one specified in the manifest file." );
		}

		$manifest->set( "name", $name );

		// dispatch event

		$event = new Events\UninstallPackageEvent( $this, $name, $manifest, $package );
		$dispatcher = $this->event->dispatcher( $event->getName() );
		$dispatcher->dispatch( $event );

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Package uninstall aborted." );
		}

		$file = $this->zip( $name, $package->getVersion() );
		$size = filesize( $file );

		$this->addDebug( "Created backup file \"{$file}\" for the \"{$name}\" package, \"{$size}\" byte(s)." );

		$this
			->db
			->plainBuilder()
			->from( TemplatePackages_SchemeDesigner::getTableName() )
			->where( "id", $package->getId() )
			->limit( 1 )
			->delete();

		$filesystem = $this->filesystem;

		$view = Path::view( $name );
		$filesystem->deleteDirectory( $view, true ) ||
		$this->addError( "Failed to delete \"{$view}\" folder." );

		$assets = Path::assets( $name );
		$filesystem->isDirectory( $assets ) && (
			$filesystem->deleteDirectory( $assets, true ) ||
			$this->addError( "Failed to delete \"{$assets}\" folder." )
		);

		$dispatcher->complete( $package->getId() );
		$this->addDebug( Text::text( "The \"%s\" package was successfully uninstalled.", $name ) );

		return $this;
	}

	/**
	 * Create new package.
	 *
	 * @param string $name
	 *
	 * @return PackageProcessor
	 *
	 * @throws DBALException
	 * @throws PackageNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 * @throws \Throwable
	 */
	public function create( string $name ): PackageProcessor
	{
		$this->clearLastInsertId();
		if( !ModuleHelper::validKey( $name ) )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "Invalid package name \"{$name}\"." );
		}

		$name = str_replace( "_", "-", $name );
		if( PackageHelper::exists( $name ) )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "The \"{$name}\" package already exists." );
		}

		// dispatch event

		$event = new Events\CreatePackageEvent( $this, $name );
		$dispatcher = $this->event->dispatcher( $event->getName() );
		$dispatcher->dispatch( $event );

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "Package create aborted." );
		}

		$json = [
			"name" => $name,
			"version" => "1.0",
			"addon" => true,
			"templates" => [],
		];

		$file = Path::view( $name . "/manifest.json" );
		if( !$this->writeFileExport( $file, $json ) )
		{
			throw new Exceptions\FilesystemException( "Could not create package manifest file \"{$file}\"." );
		}

		try
		{
			$id = (int) $this
				->db
				->plainBuilder()
				->from( TemplatePackages_SchemeDesigner::getTableName() )
				->insertGetId( [
					"module_id" => $this->getModuleId(),
					"name" => $name,
					"version" => $json["version"],
					"addon" => true,
				] );

			$this->setLastInsertId( $id );
		} catch( DBALException $e )
		{
			$this->filesystem->deleteDirectory( Path::view( $name ), true );
			throw $e;
		}

		$dispatcher->complete( $this->getLastInsertId() );
		$this->addDebug( Text::text( "The \"%s\" package was successfully created.", $name ) );

		return new PackageProcessor( $name );
	}

	/**
	 * Get scheme designer package.
	 *
	 * @param string $name
	 *
	 * @return TemplatePackages_SchemeDesigner
	 *
	 * @throws DBALException
	 * @throws PackageNotFoundException
	 */
	private function package( string $name ): TemplatePackages_SchemeDesigner
	{
		$row = $this
			->db
			->plainBuilder()
			->from( TemplatePackages_SchemeDesigner::getTableName() )
			->where( "name", $name )
			->first();

		if( !$row )
		{
			throw new PackageNotFoundException( "The \"{$name}\" template package not found." );
		}

		$package = new TemplatePackages_SchemeDesigner( $row, $this->db );
		if( $package->getModuleId() !== $this->getModuleId() )
		{
			throw new ExpectedModuleException( "The specified \"{$name}\" package belongs to another module." );
		}

		return $package;
	}

	/**
	 * Create backup package zip archive.
	 *
	 * @param string $name
	 * @param string $version
	 *
	 * @return string
	 */
	private function zip( string $name, string $version ): string
	{
		$path = Path::addons( $this->getModule()->getKey() . "/resources/packages" );
		$file = $path . DIRECTORY_SEPARATOR . $name . '-v' . $version . '-t' . date( 'dmYHis' ) . ".zip";
		if( file_exists( $file ) )
		{
			throw new Exceptions\FilesystemException( "Could not create and open zip file \"{$file}\". File already exists." );
		}

		$this->filesystem->exists( $path ) || $this->filesystem->makeDirectory( $path, 0777, true );
		$zip = new ZipArchive();
		if( $zip->open( $file, ZipArchive::CREATE ) !== true )
		{
			throw new Exceptions\FilesystemException( "Could not create and open zip file \"{$file}\"." );
		}

		$viewDir = Path::view( $name );
		$assetsDir = Path::assets( $name );
		$this->toZip( $zip, $viewDir );
		$this->filesystem->isDirectory( $assetsDir ) && $this->toZip( $zip, $assetsDir );
		if( !$zip->close() )
		{
			throw new Exceptions\FilesystemException( "Could not create zip file \"{$file}\". Close error." );
		}

		return $file;
	}

	/**
	 * Add files to zip.
	 *
	 * @param ZipArchive $zip
	 * @param string     $path
	 */
	private function toZip( ZipArchive $zip, string $path )
	{
		$iter = new Iterator( $path );
		$iter->each( function( \SplFileInfo $file, $depth, $fullPath, $relativePath ) use ( $zip ) {
			$path = $relativePath . "/" . $file->getBasename();
			if( !( $file->isDir() ? $zip->addEmptyDir( $path ) : $zip->addFile( $file->getPathname(), $path ) ) )
			{
				throw new Exceptions\FilesystemException( 'Failed to create backup package. Could not add file "' . $path . '" to the zip archive.' );
			}
		}, 0, Iterator::TYPE_FILE | Iterator::TYPE_DIRECTORY );
	}

	/**
	 * Unzips the package file and checks the file system for installation.
	 *
	 * @param string $name
	 * @param bool   $update
	 *
	 * @return Prop
	 *
	 * @throws PackageNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	private function unZip( string $name, bool $update = false ): Prop
	{
		$file = $this->getModule()->getPathname() . "resources" . DIRECTORY_SEPARATOR . "packages" . DIRECTORY_SEPARATOR . $name . ".zip";
		if(!file_exists($file))
		{
			throw new Exceptions\PackageNotFoundException( "PackageManagerProcessor zip file \"{$name}.zip\" not found." );
		}

		$fs = $this->filesystem;
		$view = Path::view( $name );
		$assets = Path::assets( $name );

		if( !$update )
		{
			if( $fs->exists( $view ) )
			{
				throw new Exceptions\PackageInvalidArgumentsException( "Warning! The \"{$name}\" view directory already exists." );
			}
			if( $fs->exists( $assets ) )
			{
				throw new Exceptions\PackageInvalidArgumentsException( "Warning! The \"{$name}\" assets directory already exists." );
			}
		}

		$zip = new ZipArchive();
		if( $zip->open( $file ) !== true )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "Could not open zip file \"{$file}\"." );
		}

		$tmp = sys_get_temp_dir();
		$tmpZipDir = $tmp . DIRECTORY_SEPARATOR . md5("id-" . $this->getModuleId() . "-" . $name . "-" . time());

		if( !$tmp || !$fs->isWritable($tmp) || !$fs->makeDirectory($tmpZipDir) )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "Unable to unpack zip package file, tmp directory is not writable." );
		}

		$tmp = $tmpZipDir;
		if( !$zip->extractTo( $tmp ) || !$zip->close() )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "Unable to extract zip package file." );
		}

		$manifest = $tmp . DIRECTORY_SEPARATOR . "manifest.json";
		$data = ResourceJson::pathToJson( $manifest );

		if( isset( $data["name"] ) && $data["name"] !== $name )
		{
			throw new Exceptions\PackageInvalidArgumentsException( "The package name does not match the one specified in the manifest file." );
		}

		$prop = new Prop( $data );
		$prop->set( "name", $name );
		$prop->set( "__dirs", compact( 'assets', 'view', 'tmp' ) );
		return $prop;
	}

	/**
	 * Copies files from the archive and adds or updates information in the database.
	 *
	 * @param int|null $id
	 * @param Prop     $manifest
	 * @param \Closure $failure
	 */
	private function createPackageFromZip( ? int $id, Prop $manifest, \Closure $failure )
	{
		$dirs = $manifest->get( "__dirs" );
		$name = $manifest->get( "name" );
		$filesystem = $this->filesystem;

		$tmp = $dirs["tmp"] . DIRECTORY_SEPARATOR;
		$fromManifest = $tmp . "manifest.json";
		$fromView = $tmp . "view";
		$fromAssets = $tmp . "assets";

		// create empty directory
		$filesystem->exists( $dirs["view"] ) || $filesystem->makeDirectory( $dirs["view"], 0777, true );

		// manifest.json file
		if( !$filesystem->copy( $fromManifest, $dirs["view"] . DIRECTORY_SEPARATOR . "manifest.json" ) )
		{
			throw $failure( new Exceptions\FilesystemException( "Failed to copy \"{$name}\" package manifest file." ) );
		}

		// view dir
		if( $filesystem->isDirectory( $fromView ) && !$filesystem->copyDirectory( $fromView, $dirs["view"] ) )
		{
			throw $failure( new Exceptions\FilesystemException( "Failed to copy \"{$name}\" package data to the \"view\" folder." ) );
		}

		// assets dir
		if( $filesystem->isDirectory( $fromAssets ) && !$filesystem->copyDirectory( $fromAssets, $dirs["assets"] ) )
		{
			throw $failure( new Exceptions\FilesystemException( "Failed to copy \"{$name}\" package data to the \"assets\" folder." ) );
		}

		// write data to database

		try
		{
			$builder = $this
				->db
				->plainBuilder()
				->from( TemplatePackages_SchemeDesigner::getTableName() );

			if( $id === null )
			{
				$id = (int) $builder
					->insertGetId( [
						"module_id" => $this->getModuleId(),
						"name" => $name,
						"version" => $manifest->getOr( "version", "1.0" ),
						"addon" => $manifest->getOr( "addon", false ),
					] );

				$this->setLastInsertId( $id );
			}
			else
			{
				$builder->update( [
					"version" => $manifest->getOr( "version", "1.0" ),
				] );
			}
		} catch( DBALException $e )
		{
			throw $failure( $e );
		}
	}
}