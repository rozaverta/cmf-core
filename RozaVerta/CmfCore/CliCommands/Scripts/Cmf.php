<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 20:32
 */

namespace RozaVerta\CmfCore\CliCommands\Scripts;

use Doctrine\DBAL\DBALException;
use InvalidArgumentException;
use RozaVerta\CmfCore\Cli\IO\ConfigOption;
use RozaVerta\CmfCore\Cli\IO\Option;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Event\EventManager;
use RozaVerta\CmfCore\Filesystem\Config;
use RozaVerta\CmfCore\Log\Log;
use RozaVerta\CmfCore\Log\Logger;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Module\ModuleHelper;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Manifest as ModuleCoreManifest;
use RozaVerta\CmfCore\Helper\PhpExport;
use RozaVerta\CmfCore\Schemes\TemplatePackages_SchemeDesigner;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Workshops\Module\Events\InstallModuleEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\ModuleEvent;
use RozaVerta\CmfCore\Workshops\Module\ModuleComponent;
use RuntimeException;

/**
 * Class Cmf
 *
 * @package RozaVerta\CmfCore\CliCommands\Scripts
 */
class Cmf extends AbstractScript
{
	use ScriptUserTrait;

	protected $services = [ "app", "phpExport" ];

	/**
	 * @var Config
	 */
	private $systemConfig;

	/**
	 * @var ModuleCoreManifest
	 */
	private $moduleConfig;

	/**
	 * @return ModuleCoreManifest
	 *
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function getCoreManifest(): ModuleCoreManifest
	{
		if( ! isset($this->moduleConfig) )
		{
			$this->moduleConfig = new ModuleCoreManifest();
		}

		return $this->moduleConfig;
	}

	/**
	 * Run install
	 *
	 * @throws \Throwable
	 */
	public function install()
	{
		$this->getHost();
		if( $this->isInstall() )
		{
			throw new InvalidArgumentException("System already installed");
		}

		if( $this->inInstallUpdateProgress() )
		{
			throw new RuntimeException("Warning! System is update, please wait");
		}

		// assets
		$this->checkDir(APP_ASSETS_PATH, "assets", true);

		// application
		$this->checkDir(APP_PATH, "application");

		$directories = [
			"config"    => false,
			"resources" => false,
			"logs"      => true,
			"cache"     => true,
			"addons"    => true,
			"view"      => true
		];

		foreach($directories as $dir => $www_data)
		{
			$this->checkDir( APP_PATH . $dir, $dir, $www_data );
		}

		// configs

		// 1. /config/system.php

		$file = $this->getSystemConfig();
		if( ! $file->fileExists() )
		{
			$data = $this->installConfigSystem();
			$data["install"] = false;
			$this->reloadSystemConfig($data);
		}

		// 2. /boot.php

		$file = APP_PATH . "boot.php";
		if( ! file_exists($file) )
		{
			$this->writePhpFile($file, null);
		}

		// 3. /config/url.php

		$file = new Config("url");
		if( ! $file->fileExists() )
		{
			$this->writeConfig($file, $this->installConfigUrl());
		}

		$io = $this->getIO();

		// 4. /config/db.php

		$file = new Config("db");
		$file->reload();
		if( !$file->has( "default" ) || $io->confirm( "Override database config (y/n)? " ) )
		{
			$this->writeConfig($file, $this->installConfigDb( $file->toArray() ));
		}

		// Check database connection

		try {
			if( ! DatabaseManager::connection()->ping() )
			{
				throw new DBALException("Database connection ping failed");
			}
		}
		catch( DBALException $e ) {
			throw new InvalidArgumentException("Error database connection: " . $e->getMessage());
		}

		$io->write("<info>$</info> Database connection is created");

		if( $io->confirm("<info>$</info> The basic setting was successful. Do you want to run the installation (y/n)? ") )
		{
			// set main package as addon, so that the next update will not overwrite the package

			$io->confirm("<info>$</info> Do you want to use the main package as an addon (y/n)? ") &&
			EventManager::getInstance()
				->listen(ModuleEvent::eventName(), function (ModuleEvent $event) {
					if($event instanceof InstallModuleEvent && $event->getProcessor()->getModuleId() === 1)
					{
						return function(string $action, ? ModuleInterface $module) {
							if($action === "install")
							{
								DatabaseManager::connection()
									->builder( TemplatePackages_SchemeDesigner::getTableName() )
									->where("name", "main")
									->where("module_id", $module->getId())
									->update([
										"addon" => true
									]);
							}
						};
					}

					return null;
				});

			$this->reloadSystemConfig(["status" => "install-progress"]);
			$this->getWmp(true)->install();
			$this->reloadSystemConfig(["status" => "install", "install" => true], true);
			$this->goodBy();
		}
		else
		{
			$this->goodBy("Installation aborted");
		}
	}

	/**
	 * Run update
	 *
	 * @param bool $force
	 *
	 * @throws \Throwable
	 */
	public function update( bool $force = false )
	{
		$this->getHost();

		if( !$this->isInstall() )
		{
			throw new InvalidArgumentException("System not installed yet");
		}

		if( $this->inInstallUpdateProgress() )
		{
			throw new RuntimeException("Warning! System is update, please wait");
		}

		$status = $this->getSystemConfig()->get("status");

		$this->reloadSystemConfig(["status" => "update-progress"]);
		$this->getWmp()->update($force);
		$this->reloadSystemConfig(["status" => $status], true);
		$this->goodBy();
	}

	/**
	 * Run uninstall
	 *
	 * @throws \Throwable
	 */
	public function uninstall()
	{
		$io = $this->getIO();
		$uninstall =
			$io->confirm("Do you want to delete the system (y/n)? ") &&
			$io->confirm("Are you sure (y/n)? ") &&
			$io->confirm("Really (y/n)? ") &&
			$io->ask("<error>Attention!</error> All data will be deleted. To uninstall the system, enter <comment>UNINSTALL</comment>: ") === "UNINSTALL";

		$io->write("");
		if( ! $uninstall )
		{
			$io->write("Ohh, it's possible to breathe out... Otherwise I've already been afraid!");
		}
		else
		{
			$flag = 0;
			if( $io->confirm("Remove database tables (y/n)? ") ) $flag = ModuleComponentCore::UNINSTALL_DATABASE;
			if( $io->confirm("Remove assets files (y/n)? ") ) $flag = $flag | ModuleComponentCore::UNINSTALL_ASSETS;
			if( $io->confirm("Remove application files (y/n)? ") ) $flag = $flag | ModuleComponentCore::UNINSTALL_APPLICATION;

			$this
				->getWmp()
				->uninstall($flag);
		}
	}

	/**
	 * Show default menu
	 *
	 * @throws \Throwable
	 */
	public function menu()
	{
		if( $this->isHost() )
		{
			$this->hostMenu();
		}
		else
		{
			$variant = $this
				->getIO()
				->askOptions([
					new Option("about", 1),
					new Option("enter hostname and open menu", 2),
					new Option("exit")
				]);

			if($variant === 1) $this->about();
			else if($variant === 2) $this->hostMenu();
			else $this->goodBy();
		}
	}

	/**
	 * Show host menu
	 *
	 * @throws \Throwable
	 */
	public function hostMenu()
	{
		$this->getHost();
		$io = $this->getIO();

		if( $this->isInstall() )
		{
			$variant = $io
				->askOptions([
					new Option("update the system", "update"),
					new Option("update the system forcibly, ignore version", "update_force"),
					new Option("uninstall the system", "uninstall"),
					new Option("exit")
				], APP_HOST);
		}
		else
		{
			$variant = $io
				->askOptions([
					new Option("install the system", "install"),
					new Option("exit")
				], APP_HOST);
		}

		switch( $variant )
		{
			case "install": $this->install(); break;
			case "update":
			case "update_force": $this->update($variant === "update_force"); break;
			case "uninstall": $this->uninstall(); break;
			default: $this->goodBy(); break;
		}
	}

	/**
	 * Show about info
	 *
	 * @throws \Throwable
	 */
	public function about()
	{
		$conf = $this->getCoreManifest();
		$manifest = new Prop($conf->getManifestData());
		$io = $this->getIO();

		// info, name, description, version, build, date

		$io->write( $manifest->get( "title", "Elastic-CMF" ) );
		$info = ["version", "build", "license", "description", "date"];
		foreach($info as $key)
		{
			if( $manifest->has( $key ) )
			{
				$value = $manifest->get($key);
				if( $key === 'version' ) {
					$value = '<info>' . $manifest->get($key) . '</info>';
				}
				$io->write(ucfirst($key) . ": " . $value);
			}
		}

		// info about author

		$author = $manifest->get( "authors", [] );
		$at = [];

		if( is_array($author) && count($author) > 0 )
		{
			foreach($author as $one)
			{
				$at[] = $this->getAuthor($one);
			}
		}
		else if( $manifest->has( 'author' ) )
		{
			$at[] = $this->getAuthor( $manifest->get('author') );
		}

		$atn = count($at);
		$atn > 0 && $io->write(($atn > 1 ? 'Authors: ' : 'Author: ') . implode(', ', $at));
	}

	// private

	/**
	 * @param $author
	 *
	 * @return string
	 */
	private function getAuthor($author): string
	{
		if( is_object($author) )
		{
			$author = get_object_vars($author);
		}

		if( ! is_array($author) )
		{
			return (string) $author;
		}

		if( !isset($author['name']) )
		{
			return $author['email'] ?? 'unknown';
		}

		$at = trim($author['name']);
		if( isset($author['email']) )
		{
			$at .= ' <' . $author['email'] . '>';
		}

		return $at;
	}

	/**
	 * @return Config
	 */
	private function getSystemConfig()
	{
		if( ! isset($this->systemConfig) )
		{
			$this->systemConfig = new Config("system");
			$this->systemConfig->reload();
		}

		return $this->systemConfig;
	}

	/**
	 * @param array $data
	 * @param bool $final
	 *
	 * @throws \Throwable
	 */
	private function reloadSystemConfig(array $data, bool $final = false)
	{
		$conf = $this->getSystemConfig();

		if($final)
		{
			$moduleConfig = $this->getCoreManifest();
			$data["name"] = $moduleConfig->getTitle();
			$data["version"] = $moduleConfig->getVersion();
		}

		$this->writeConfig($conf, $data, $final);
	}

	/**
	 * @param bool $install
	 *
	 * @return ModuleComponent
	 *
	 * @throws \Throwable
	 */
	private function getWmp( bool $install = false ): ModuleComponent
	{
		$conf = $this->getCoreManifest();

		if( ModuleHelper::exists($conf->getName(), $moduleId) )
		{
			if( $install )
			{
				throw new InvalidArgumentException($conf->getName() . " module already installed previously");
			}
		}
		else if( ! $install )
		{
			throw new InvalidArgumentException($conf->getName() . " module not installed");
		}
		else
		{
			$moduleId = 1;
		}

		$module = WorkshopModuleProcessor::module($moduleId);
		$wmp = new ModuleComponent($module);

		$wmp->listenLog(function(Log $log) {

			$isError = Logger::isHighLevel($log->getLevel());

			$t = $isError ? "error" : "info";
			$e = $isError ? ucfirst(strtolower($log->getLevelName())) . ":" : "\$";

			$this
				->getIO()
				->write("<{$t}>" . $e . "</{$t}> " . $log->message());
		});

		return $wmp;
	}

	/**
	 * @param Config $config
	 * @param array $data
	 * @param bool $debug
	 *
	 * @throws \Throwable
	 */
	private function writeConfig(Config $config, array $data = [], bool $debug = true)
	{
		$exists = $config->fileExists();

		$config
			->reload()
			->merge($data, true)
			->save();

		if($debug)
		{
			$file = $config->getFilename();
			$this
				->getIO()
				->write("<info>\$</info> Config {$file} file was successfully " . ($exists ? "updated" : "created"));
		}
	}

	/**
	 * @param string $file
	 * @param $content
	 * @param bool $www_data
	 *
	 * @throws \Throwable
	 */
	private function writePhpFile(string $file, $content, bool $www_data = false)
	{
		$text = '<?php defined("CMF_CORE") || exit;' . "\n";

		if( is_array($content) )
		{
			$text .= $this->phpExport
					->config(PhpExport::SHORT_ARRAY_SYNTAX | PhpExport::ARRAY_PRETTY_PRINT)
					->data($content) . "\nreturn \$data;\n";
		}
		else if( is_string($content) )
		{
			$text .= $content;
		}

		$exists = file_exists($file);

		if( $fo = @ fopen( $file, "wa+" ) )
		{
			if( @ flock( $fo, LOCK_EX ) )
			{
				flock(  $fo, LOCK_UN );
				fwrite( $fo, $text );
				fflush( $fo );
				flock(  $fo, LOCK_UN );
			}

			@ fclose( $fo );

			$ready = @ file_get_contents($file);

			if( $ready && md5($ready) === md5($text) )
			{
				$this
					->getIO()
					->write("<info>\$</info> The {$file} file was successfully " . ($exists ? "updated" : "created"));

				$www_data && $this->chownUserData($file);
				return;
			}

			file_exists($file) && @ unlink($file);
		}

		throw new InvalidArgumentException("<error>Error:</error> cannot create the config file {$file}");
	}

	/**
	 * @param string $file
	 */
	private function chownUserData(string $file)
	{
		$user  = $this->getScriptUser();
		$group = $user;
		$io = $this->getIO();

		if( function_exists('chown') )
		{
			if( @ chown($file, $user) ) $io->write("<info>$ chown</info> " . $user);
			else $io->write("<error>Wrong:</error> chown error, cannot change user info");
		}

		if( function_exists('chgrp') )
		{
			if( @ chgrp($file, $group) ) $io->write("<info>$ chgrp</info> " . $group);
			else $io->write("<error>Wrong:</error> chgrp error, cannot change group info");
		}
	}

	/**
	 * @param string $dir
	 * @param string $type
	 * @param bool $www_data
	 *
	 * @throws \Throwable
	 */
	private function checkDir(string $dir, string $type, bool $www_data = false)
	{
		$dir = rtrim( $dir, DIRECTORY_SEPARATOR );
		if( ! is_dir($dir) )
		{
			if( is_file($dir) ) throw new InvalidArgumentException(ucfirst($type) . " dir '{$dir}' is file");
			if( is_link($dir) ) throw new InvalidArgumentException(ucfirst($type) . " dir '{$dir}' is link");
			if( ! @ mkdir($dir, $www_data ? 0777 : 0755) ) throw new InvalidArgumentException("Cannot create the {$type} dir '{$dir}'");

			$this
				->getIO()
				->write("<info>\$</info> Create the {$type} directory: {$dir}");

			$www_data && $this->chownUserData($dir);
		}
	}

	/**
	 * @return mixed
	 */
	private function installConfigSystem()
	{
		return $this
			->getIO()
			->askConfig([
				new ConfigOption("siteName", "Enter site name", ["title" => "Site name"]),
				new ConfigOption("debug", "Debug global [<info>%s</info>]", ["default" => true, "type" => "boolean", "title" => "Debug"]),
				new ConfigOption("debugLevel", "Debug level", ["ignore_empty" => true, "enum" => [
					"all",
					"info",
					"debug",
					"error"
				]])
			], "System config");
	}

	/**
	 * @return mixed
	 */
	private function installConfigUrl()
	{
		return $this
			->getIO()
			->askConfig([
				new ConfigOption("mode", "Enter rewrite url mode [<info>%s</info>]", [
					"title" => "Rewrite mode",
					"default" => "rewrite",
					"enum" => [
						"rewrite",
						"get"
					]])
			], "Url config");
	}

	/**
	 * @param array $load
	 * @return array
	 */
	private function installConfigDb( array $load = [] )
	{
		$def = $this
			->getIO()
			->askConfig([
				new ConfigOption("driver", "Driver [<info>%s</info>]", [
					"title" => "Database driver",
					"default" => "pdo_mysql",
					"enum" => [
						"pdo_mysql", "pdo_pgsql"
					]]),
				new ConfigOption("host", "Enter host name [<info>%s</info>]", ["default" => "localhost", "title" => "Database host name"]),
				new ConfigOption("port", "Enter port", ["title" => "Database port", "type" => "number"]),
				new ConfigOption("dbname", "Enter base name", ["default" => true, "title" => "Database name"]),
				new ConfigOption("prefix", "Enter table prefix", ["title" => "Database table prefix"]),
				new ConfigOption("charset", "Enter charset [<info>%s</info>]", ["title" => "Database charset", "default" => "utf8"]),
				new ConfigOption("collation", "Enter charset collation [<info>%s</info>]", ["title" => "Database collation", "default" => "{charset}_general_ci"]),
				new ConfigOption("user", "Enter user name [<info>%s</info>]", ["title" => "Database user", "default" => "root"]),
				new ConfigOption("password", "Enter password", ["title" => "Database password"]),
			], "Database info (default connection)", $load["default"] ?? []);

		if(isset($def["port"] ) && $def["port"] < 1)
		{
			unset($def["port"]);
		}

		$load["default"] = $def;

		return $load;
	}
}