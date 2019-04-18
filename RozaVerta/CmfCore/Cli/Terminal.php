<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace RozaVerta\CmfCore\Cli;

use RozaVerta\CmfCore\Exceptions\Exception;
use RozaVerta\CmfCore\Manifest;
use RozaVerta\CmfCore\Module\ModuleManifest;
use RozaVerta\CmfCore\Schemes\Modules_SchemeDesigner;
use RozaVerta\CmfCore\Events\ThrowableEvent;
use RozaVerta\CmfCore\Traits\ApplicationTrait;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\EventDispatcher\EventDispatcher;

class Terminal
{
	use ApplicationTrait;

	/**
	 * Current system name
	 *
	 * @var string
	 */
	private $name;

	/**
	 * Current system version
	 *
	 * @var string
	 */
	private $version;


	private $moduleCoreConfig;

	/**
	 * Terminal constructor.
	 * @throws Exception
	 * @throws \ReflectionException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function __construct()
	{
		if( ! defined('APP_CORE_PATH') )
		{
			throw new Exception("System is not loaded");
		}

		if( ! defined('SERVER_CLI_MODE') || ! SERVER_CLI_MODE )
		{
			throw new Exception("Run php as cli");
		}

		$this->appInit();

		if( $this->app->host->isDefined() )
		{
			$status = $this->app->system("status");
			if( strpos($status, "-progress") > 0 || $status === "progress" )
			{
				throw new Exception("Warning! System is update, please wait");
			}
		}

		// load manifest resource
		$this->moduleCoreConfig = new Manifest();
		$this->name = $this->moduleCoreConfig->getTitle();
		$this->version = $this->moduleCoreConfig->getVersion();
	}

	/**
	 * @return int
	 * @throws Exception
	 */
	public function run()
	{
		$application = new Application( $this->name, $this->version );

		// register all commands

		$this->load($application);

		// add system throwable
		$dispatcher = new EventDispatcher();
		$dispatcher->addListener(ConsoleEvents::ERROR, function (ConsoleErrorEvent $event) {
			$this->app->event->dispatch( new ThrowableEvent( $event->getError() ));
		});

		$application->setDispatcher($dispatcher);

		return $application->run();
	}

	/**
	 * @param Application $application
	 * @return int
	 * @throws Exception
	 */
	public function load(Application $application)
	{
		static $load = -1;

		if( $load > -1 )
		{
			return $load;
		}

		$load = $this->loadModule( $application, $this->moduleCoreConfig );
		if( $load === false )
		{
			throw new Exception("Default commands not registered");
		}

		if( $this->app->isInstall() )
		{
			/** @var Modules_SchemeDesigner[] $modules */
			$modules = $this
				->app
				->db
				->table(Modules_SchemeDesigner::class)
				->where("install", true)
				->where("id", "<>", 1)
				->get();

			foreach( $modules as $module )
			{
				$count = $this->loadModule( $application, $module->getManifest() );
				if( $count !== false )
				{
					$load += $count;
				}
			}
		}

		return $load;
	}

	/**
	 * @param Application $application
	 * @param ModuleManifest $config
	 * @return bool|int
	 * @throws Exception
	 */
	private function loadModule( Application $application, ModuleManifest $config )
	{
		$path = $config->getPathname() . "CliCommands";
		if( ! is_dir($path) )
		{
			return false;
		}

		$name_space = $config->getNamespaceName() . "CliCommands\\";
		$key = md5($path);

		if( $this->app->isInstall() )
		{
			$cache = $this->app->cache->newCache($key, "console");
			if( $cache->ready() )
			{
				$commands = $cache->import();
				foreach($commands as $command)
				{
					$class_name = $command["class_name"];
					$application->add(new $class_name( $command ));
				}
				return count($commands);
			}
		}

		try {
			$iterator = new \FilesystemIterator($path);
		}
		catch( \UnexpectedValueException $e ) {
			throw new Exception("Cannot ready terminal directory: " . $e->getMessage());
		}

		$commands = [];

		/** @var \SplFileInfo $file */
		foreach( $iterator as $file )
		{
			$name = $file->getFilename();

			// valid file name
			if( $name[0] !== "." && ! $file->isLink() && $file->isFile() && $file->getExtension() === "php" )
			{
				// check class exists
				$name = $file->getBasename(".php");
				$class_name = $name_space . $name;
				if( ! class_exists($class_name, true) )
				{
					continue;
				}

				// check subclass
				// valid only \RozaVerta\CmfCore\Cli\AbstractCliCommand
				$comment = new PhpLexer\CommentClass($class_name);
				if( ! $comment->getReflection()->isSubclassOf(AbstractCliCommand::class) )
				{
					continue;
				}

				// set base properties
				// command name, class name, description, help, author
				$command =
					[
						"name" => preg_replace_callback('/[A-Z]/', static function($m) { return '-' . lcfirst($m[0]); }, lcfirst($name)),
						"class_name" => $class_name
					];

				if( $comment->hasDescription() )
				{
					$command["description"] = (string) $comment->getDescription();
				}

				if( $comment->hasParam("help") )
				{
					$command["help"] = (string) $comment->getParam("help");
				}

				if( $comment->hasParam("author") )
				{
					$command["author"] = (string) $comment->getParam("author");
				}

				$class_name = $command["class_name"];
				$application->add(new $class_name( $command ));

				$commands[] = $command;
			}
		}

		$length = count($commands);
		if( $length < 1 )
		{
			return 0;
		}

		if( isset($cache) )
		{
			$cache->export($commands);
		}

		return $length;
	}
}