<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.05.2019
 * Time: 0:12
 */

namespace RozaVerta\CmfCore\CliCommands\Scripts;

use InvalidArgumentException;
use RozaVerta\CmfCore\Cli\IO\Option;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Filesystem\Config;
use RozaVerta\CmfCore\Interfaces\WorkshopInterface;
use RozaVerta\CmfCore\Log\Log;
use RozaVerta\CmfCore\Log\Logger;
use RozaVerta\CmfCore\Module\Exceptions\ModuleNotFoundException;
use RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException;
use RozaVerta\CmfCore\Module\Exceptions\ResourceReadException;
use RozaVerta\CmfCore\Module\ModuleHelper;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Workshops\Module\Events\ModuleEvent;
use RozaVerta\CmfCore\Workshops\Module\ModuleComponent;
use RozaVerta\CmfCore\Workshops\Module\ModuleRegister;
use RuntimeException;
use Throwable;

/**
 * Class Module
 *
 * @package RozaVerta\CmfCore\CliCommands\Scripts
 */
class Module extends AbstractScript
{
	/**
	 * Open default menu
	 *
	 * @throws Throwable
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
					new Option("enter hostname and open menu", 1),
					new Option("exit")
				]);

			if($variant === 1) $this->hostMenu();
			else $this->goodBy();
		}
	}

	/**
	 * Open host menu
	 *
	 * @throws Throwable
	 */
	public function hostMenu()
	{
		$this->host();

		$variant = $this
			->getIO()
			->askOptions([
				new Option("register module", "register"),
				new Option("unregister module", "unregister"),
				new Option("select module and open module menu", "module"),
				new Option("exit")
			], APP_HOST);

		switch( $variant )
		{
			case "register": $this->register(); break;
			case "unregister": $this->unregister(); break;
			case "module": $this->moduleMenu(); break;
			default: $this->goodBy(); break;
		}
	}

	/**
	 * Open module menu
	 *
	 * @param string $module
	 * @throws ModuleNotFoundException
	 * @throws ResourceNotFoundException
	 * @throws ResourceReadException
	 * @throws Throwable
	 */
	public function moduleMenu( string $module = "" )
	{
		$this->host();
		$io = $this->getIO();
		$module = trim($module);

		if(strlen($module) < 1)
		{
			$module = $io->askTest("Enter module name (<info>*</info> for list): ", function(string $name) {
				$name = trim($name);
				return strlen($name) > 0;
			});

			$this->moduleMenu($module);
		}
		else
		{
			if( $module === "*" )
			{
				// show all
				$all = DatabaseManager
					::table(Modules_SchemeDesigner::getTableName())
					->where("id", "!=", 1)
					->orderBy("name")
					->select(["name", "version", "install"])
					->project(function(array $row) {
						return [
							"name" => $row["name"],
							"title" => "Module '" . $row["name"] . "' v" . $row["version"],
							"install" => is_numeric($row["install"]) ? ($row["install"] > 0) : (bool) $row["install"]
						];
					});

				if(count($all) < 1)
				{
					$io->write("<error>Wrong:</error> modules not found");
				}
				else
				{
					$menu = [];
					foreach($all as $item)
					{
						$answer = $item["title"];
						if($item["install"])
						{
							$answer .= " <info>[install]</info>";
						}
						$menu[] = new Option($answer, $item["name"]);
					}

					$this->moduleMenu( $io->askOptions($menu, "Select module") );
				}

				return;
			}

			$name = ModuleHelper::toNameStrict($module);

			if( is_null($name) )
			{
				$io->write("<error>Wrong:</error> invalid module name - {$module}");
				if($this->isContinue())
				{
					$this->moduleMenu();
				}
			}

			else if( ! ModuleHelper::exists($name, $moduleId) )
			{
				$io->write("<error>Wrong:</error> module '{$name}' was not registered");
				if($this->isContinue())
				{
					$this->moduleMenu();
				}
			}

			else if( $moduleId === 1 )
			{
				$io->write("<error>Wrong:</error> use <info>'cmf'</info> command for Core module");
				if($this->isContinue())
				{
					$this->moduleMenu();
				}
			}

			else
			{
				$module = WorkshopModuleProcessor::module($moduleId);

				if($module->isInstall())
				{
					$menu = [
						new Option("update module", "update"),
						new Option("update module forcibly, ignore version", "update_force"),
						new Option("uninstall module", "uninstall")
					];
				}
				else
				{
					$menu = [
						new Option("install module", "install"),
						new Option("unregister module", "unregister")
					];
				}

				$menu[] = new Option("exit");

				$variant = $io->askOptions($menu, $module->getTitle());
				switch($variant)
				{
					case "install":
					case "uninstall":
					case "update":
					case "update_force":
						$this->moduleProcess($module, $variant);
						break;

					case "register":
					case "unregister":
						$this->registerProcess($module->getNamespaceName(), $variant);
						break;

					default:
						$this->goodBy();
						break;
				}
			}
		}
	}

	/**
	 * Run install action
	 *
	 * @param string $name
	 *
	 * @throws Throwable
	 */
	public function install(string $name)
	{
		$this->host();
		$this->moduleLoadProcess($name, "install");
	}

	/**
	 * Run uninstall action
	 *
	 * @param string $name
	 *
	 * @throws Throwable
	 */
	public function uninstall(string $name)
	{
		$this->host();
		$this->moduleLoadProcess($name, "uninstall");
	}

	/**
	 * Run update module action
	 *
	 * @param string $name
	 * @param bool $force
	 *
	 * @throws Throwable
	 */
	public function update(string $name, bool $force = false)
	{
		$this->host();
		$this->moduleLoadProcess($name, $force ? "update_force" : "update");
	}

	/**
	 * Register new module
	 *
	 * @param string $namespaceName
	 *
	 * @throws Throwable
	 */
	public function register(string $namespaceName = "")
	{
		$this->host();

		$namespaceName = trim($namespaceName);
		if( !strlen($namespaceName) )
		{
			$namespaceName = $this
				->getIO()
				->askTest("Enter module namespace name: ", function(string $test) {
					$test = trim($test);
					return strlen($test) > 0;
				});
		}

		$this->registerProcess($namespaceName, "register");
	}

	/**
	 * Unregister module
	 *
	 * @param string $namespaceName
	 *
	 * @throws Throwable
	 */
	public function unregister(string $namespaceName = "")
	{
		$this->host();

		$namespaceName = trim($namespaceName);
		if( !strlen($namespaceName) )
		{
			$namespaceName = $this
				->getIO()
				->askTest("Enter module namespace name or module name: ", function(string $test) {
					$test = trim($test);
					return strlen($test) > 0;
				});
		}

		$this->registerProcess($namespaceName, "unregister");
	}

	/**
	 * @param string $moduleName
	 * @param string $process
	 *
	 * @throws Throwable
	 */
	private function moduleLoadProcess(string $moduleName, string $process)
	{
		$name = ModuleHelper::toNameStrict($moduleName);
		$io = $this->getIO();

		if( is_null($name) )
		{
			$io->write("<error>Fail:</error> invalid module name - {$moduleName}");
		}

		else if( ! ModuleHelper::exists($name, $moduleId) )
		{
			$io->write("<error>Fail:</error> module '{$name}' was not registered");
		}

		else if( $moduleId === 1 )
		{
			$io->write("<error>Fail:</error> use <info>'cmf'</info> command for Core module");
		}

		else
		{
			$this->moduleProcess(WorkshopModuleProcessor::module($moduleId), $process);
		}
	}

	/**
	 * @param WorkshopModuleProcessor $module
	 * @param string $process
	 *
	 * @throws Throwable
	 */
	private function moduleProcess(WorkshopModuleProcessor $module, string $process)
	{
		$wmp = new ModuleComponent($module);
		$io = $this->getIO();
		$dispatch = false;
		$this->processSubscribe($wmp, $dispatch);

		$config = new Config("system");
		$config->reload();

		$status = $config->get("status");

		$config
			->set("status", "progress")
			->save();

		try {
			switch($process)
			{
				case "install": $wmp->install(); break;
				case "update":
				case "update_force": $wmp->update($process === "update_force"); break;
				case "uninstall":

					if( !$io->confirm("Are you sure you want to remove the '<info>" . $module->getTitle() . "</info>' module (y/<info>n</info>)? ", false) )
					{
						$io->write("");
						$io->write("The operation was canceled. Well, thank God!");
						$this->goodBy();
						return;
					}

					// todo flag

					$io->write("");

					$flag = 0;
					if( $io->confirm("Delete module assets files (<info>y</info>/n)? ", true) ) $flag = $flag | ModuleComponent::UNINSTALL_ASSETS;
					if( $io->confirm("Delete module add-ons files (<info>y</info>/n)? ", true) ) $flag = $flag | ModuleComponent::UNINSTALL_ADDONS;
					if( $io->confirm("Delete module config files (<info>y</info>/n)? ", true) ) $flag = $flag | ModuleComponent::UNINSTALL_CONFIG;
					if( $io->confirm("Delete all versions/history of resource files (<info>y</info>/n)? ", true) ) $flag = $flag | ModuleComponent::UNINSTALL_VERSIONS_HISTORY;
					if( $io->confirm("Delete template packages (<info>y</info>/n)? ", true) ) $flag = $flag | ModuleComponent::UNINSTALL_PACKAGES;

					$wmp->uninstall($flag);
					break;
			}
		}
		catch(EventAbortException $e) {
			$io->write("<error>Error:</error> " . $e->getMessage());
		}
		catch( Throwable $e) {
			if( $dispatch )
			{
				throw $e;
			}
			else
			{
				$io->write("<error>Error:</error> " . $e->getMessage());
			}
		}

		$config
			->set("status", $status)
			->save();
	}

	/**
	 * @param string $namespace
	 * @param string $process
	 *
	 * @throws Throwable
	 */
	private function registerProcess(string $namespace, string $process)
	{
		if(strpos($namespace, '/') !== false)
		{
			$namespace = str_replace('/', '\\', $namespace);
		}

		// if used module name
		if(strpos($namespace, '\\') === false && $process === "unregister")
		{
			$name = ModuleHelper::toNameStrict($namespace);
			if(! is_null($name) && ModuleHelper::exists($name, $moduleId) )
			{
				$namespace = WorkshopModuleProcessor::module($moduleId)->getNamespaceName();
			}
		}

		$wmp = new ModuleRegister($namespace);
		$io = $this->getIO();
		$dispatch = false;
		$this->processSubscribe($wmp, $dispatch);

		try {
			if($process === "register") $wmp->register();
			else if($process === "unregister") $wmp->unregister();
		}
		catch(EventAbortException $e) {
			$io->write("<error>Error:</error> " . $e->getMessage());
		}
		catch(Throwable $e) {
			if( $dispatch )
			{
				throw $e;
			}
			else
			{
				$io->write("<error>Error:</error> " . $e->getMessage());
			}
		}
	}

	/**
	 * @param WorkshopInterface $wmp
	 * @param $dispatch
	 *
	 * @throws Throwable
	 */
	private function processSubscribe( WorkshopInterface $wmp, & $dispatch )
	{
		$wmp->listenLog(function(Log $log) {

			$isError = Logger::isHighLevel($log->getLevel());

			$t = $isError ? "error" : "info";
			$e = $isError ? ucfirst(strtolower($log->getLevelName())) . ":" : "\$";

			$this
				->getIO()
				->write("<{$t}>" . $e . "</{$t}> " . $log->message());
		});

		$this
			->app
			->event
			->listen(
				ModuleEvent::eventName(),
				function (ModuleEvent $e) use (& $dispatch) { $dispatch = true; },
				1000
			);
	}

	/**
	 * Choosing a host, checking the installation or installation process
	 */
	private function host()
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
	}
}