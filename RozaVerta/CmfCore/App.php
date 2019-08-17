<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 13:37
 */

namespace RozaVerta\CmfCore;

use Closure;
use ReflectionMethod;
use ReflectionException;
use RozaVerta\CmfCore\Cache\CacheManager;
use RozaVerta\CmfCore\Cli\Terminal;
use RozaVerta\CmfCore\Exceptions\RuntimeException;
use RozaVerta\CmfCore\Route\Context;
use RozaVerta\CmfCore\Route\ContextLoader;
use RozaVerta\CmfCore\Route\Interfaces\ControllerContentOutputInterface;
use RozaVerta\CmfCore\Route\Interfaces\ControllerInterface;
use RozaVerta\CmfCore\Controllers\Welcome;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Event\EventManager;
use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Exceptions\Exception;
use RozaVerta\CmfCore\Filesystem\Filesystem;
use RozaVerta\CmfCore\Helper\Callback;
use RozaVerta\CmfCore\Helper\Data;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Helper\Str;
use RozaVerta\CmfCore\Host\HostManager;
use RozaVerta\CmfCore\Http\Request;
use RozaVerta\CmfCore\Http\Response;
use RozaVerta\CmfCore\Language\Events\LanguageEvent;
use RozaVerta\CmfCore\Language\Events\ReadyLanguageEvent;
use RozaVerta\CmfCore\Language\LanguageManager;
use RozaVerta\CmfCore\Log\LogManager;
use RozaVerta\CmfCore\Helper\PhpExport;
use RozaVerta\CmfCore\Route\Exceptions\PageNotFoundException;
use RozaVerta\CmfCore\Route\Interfaces\ControllerPackageInterface;
use RozaVerta\CmfCore\Route\Interfaces\ControllerTemplateInterface;
use RozaVerta\CmfCore\Route\Interfaces\MountPointInterface;
use RozaVerta\CmfCore\Route\Interfaces\RouterInterface;
use RozaVerta\CmfCore\Route\MountPoint;
use RozaVerta\CmfCore\Route\Url;
use RozaVerta\CmfCore\Schemes\Routers_SchemeDesigner;
use RozaVerta\CmfCore\Session\SessionManager;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;
use RozaVerta\CmfCore\View\PageCache;
use RozaVerta\CmfCore\View\View;
use Symfony\Component\Console\Application;

/**
 * Elastic Content Management Framework
 *
 * @author GoshaV [Maniako] <gosha@rozaverta.com>
 * @date 24.08.2017 00:04
 *
 * Class Els
 *
 * @property \RozaVerta\CmfCore\App                      $app
 * @property \RozaVerta\CmfCore\Log\LogManager           $log
 * @property \RozaVerta\CmfCore\Helper\PhpExport         $phpExport
 * @property \RozaVerta\CmfCore\Route\Url                $url
 * @property \RozaVerta\CmfCore\View\View                $view
 * @property \RozaVerta\CmfCore\Filesystem\Filesystem    $filesystem
 * @property \RozaVerta\CmfCore\Language\LanguageManager $lang
 * @property \RozaVerta\CmfCore\Session\SessionManager   $session
 * @property \RozaVerta\CmfCore\Database\DatabaseManager $database
 * @property \RozaVerta\CmfCore\Database\Connection      $db
 * @property \RozaVerta\CmfCore\Route\Interfaces\ControllerInterface $controller
 * @property \RozaVerta\CmfCore\Route\Context $context
 * @property \RozaVerta\CmfCore\Http\Response $response
 * @property \RozaVerta\CmfCore\Http\Request $request
 * @property \RozaVerta\CmfCore\Host\HostManager $host
 * @property \RozaVerta\CmfCore\Event\EventManager $event
 * @property \RozaVerta\CmfCore\Cache\CacheManager $cache
 *
 * @method static App getInstance()
 */
final class App
{
	use SingletonInstanceTrait;

	private $systemInit = false;

	private $hostDefined = false;

	private $ci = [];

	private $singletons = [
		'database'      => DatabaseManager::class,
		'filesystem'    => Filesystem::class,
	//	'view'          => View::class,
		'lang'          => LanguageManager::class,
	//	'url'           => Url::class,
		'phpExport'     => PhpExport::class,
		'session'       => SessionManager::class,
		'host'          => HostManager::class,
		'cache'         => CacheManager::class,
	];

	private $completable = [];

	private $system = [
		"install" => false,
	];

	/**
	 * Get system config value
	 *
	 * @param string $name
	 * @param null $default
	 * @return mixed
	 *
	 * @throws \Throwable
	 */
	public function system(string $name, $default = null)
	{
		if( ! $this->hostDefined )
		{
			if( ! $this->loaded("host") )
			{
				$this->init();
			}
			else if( $this->host->isDefined() )
			{
				$this->loadSystem();
			}
		}

		return $this->system[$name] ?? $default;
	}

	/**
	 * The system has been initialized.
	 *
	 * @return bool
	 */
	public function initialized(): bool
	{
		return $this->systemInit;
	}

	/**
	 * Initial system.
	 *
	 * @return $this
	 *
	 * @throws Exceptions\ClassNotFoundException
	 * @throws Exceptions\WriteException
	 * @throws Module\Exceptions\ResourceReadException
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function init()
	{
		static $init = false;

		if( $init )
		{
			return $this;
		}

		$init = true;

		if( ! defined("APP_CORE_PATH") )
		{
			require __DIR__ . DIRECTORY_SEPARATOR . "boot.inc.php";
		}

		$this->ci["app"] = $this;
		$this->ci['host'] = HostManager::getInstance();
		$this->host = $this->ci['host'];
		$host = $this->ci['host'];
		$host->isLoaded() || $host->reload();
		$host->isLoaded() && ! $host->isDefined() && $host->define();

		$this->loadSystem();

		$this->ci['log'] = LogManager::getInstance();
		$this->ci['event'] = EventManager::getInstance();
		$this->ci['response'] = new Response();
		$this->ci['request'] = Request::createFromGlobals();

		$this->app = $this;
		$this->log = $this->ci['log'];
		$this->event = $this->ci['event'];
		$this->response = $this->ci['response'];
		$this->request = $this->ci['request'];

		$this->singletons['db'] = function(App $app) {
			return DatabaseManager::getInstance()->getConnection();
		};

		$this->singletons['view'] = function(App $app) {

			$url = $app->url;

			$app = [];

			$app["language"] = "ru";
			$app["siteName"] = $this->system( "siteName", $url->getHost() );
			$app["assets"] = Path::assetsWeb();
			$app["charset"] = Str::encoding();
			$app["now"] = time();
			$app["fromCache"] = false;
			$app["host"] = $url->getHost();
			$app["http"] = $url->getPrefix();

			$app["route"] = [
				"url"  => $url->getUrl(),
				"base" => $url->getBase(),
				"path" => $url->getPath()
			];

			return new View( [], $app );
		};

		$this->singletons['url'] = function(App $app) {
			return new Url( Prop::prop('url') );
		};

		$event = new Events\SingletonEvent();
		$this->event->dispatch($event);

		foreach($event->getSingletons() as $name => $object)
		{
			$this->singleton($name, $object);
		}

		$this->systemInit = true;

		return $this;
	}

	/**
	 * Load singleton object.
	 *
	 * @param string $name
	 *
	 * @return object
	 *
	 * @throws Exceptions\ClassNotFoundException
	 * @throws Exceptions\WriteException
	 * @throws Module\Exceptions\ResourceReadException
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function service( string $name )
	{
		$this->init();

		if( ! isset( $this->ci[$name] ) )
		{
			if( $name == "context" )
			{
				return $this->loadContext();
			}

			if( ! isset($this->singletons[$name]) )
			{
				throw new Exceptions\NotFoundException( "Service object '{$name}' not found" );
			}

			$object = $this->singletons[$name];

			if( $object instanceof Closure )
			{
				$this->ci[$name] = $object($this);
				if( ! is_object($this->ci[$name]) )
				{
					throw new Exceptions\RuntimeException( "The result of the service '{$name}' callback is not an object" );
				}
			}
			else if( ! class_exists($object, true) )
			{
				throw new Exceptions\ClassNotFoundException( "Service class '{$object}' not found" );
			}
			else
			{
				try {
					$this->ci[$name] = ( new ReflectionMethod($object, 'getInstance') )->invoke( null );
				}
				catch( ReflectionException $e ) {
					throw new Exceptions\RuntimeException( "Invalid service '{$name}' object, " . $e->getMessage() );
				}
			}
		}

		if( ! isset($this->{$name}) )
		{
			$this->{$name} = $this->ci[$name];
		}

		if( isset($this->completable[$name]) )
		{
			$this->completable[$name]($this->ci[$name]);
		}

		return $this->ci[$name];
	}

	/**
	 * The Singleton class has been loaded.
	 *
	 * @param string $name
	 * @param bool   $autoLoad
	 * @return bool
	 * @throws Exceptions\WriteException
	 * @throws Module\Exceptions\ResourceReadException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function loaded( string $name, bool $autoLoad = false ): bool
	{
		if( isset($this->ci[$name]) )
		{
			return true;
		}

		if( $autoLoad )
		{
			try {
				$this->service( $name );
			}
			catch( NotFoundException $e ) {
				return false;
			}
		}
		else {
			return false;
		}

		return true;
	}

	/**
	 * Load and get the current context.
	 *
	 * @return Context
	 * @throws Exceptions\ClassNotFoundException
	 * @throws Exceptions\WriteException
	 * @throws Module\Exceptions\ResourceReadException
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function loadContext(): Context
	{
		if( isset($this->ci["context"]) )
		{
			return $this->ci["context"];
		}

		if( ! $this->init()->host->isDefined() )
		{
			throw new NotFoundException("The current host is not defined in the system");
		}

		if( ! defined("SERVER_CLI_MODE") || SERVER_CLI_MODE )
		{
			throw new InvalidArgumentException("Unable to use context for cli mode");
		}

		$loader = new ContextLoader(
			$this->host->getOriginalHost(), $this->url->count() ? $this->url->getPath() : ""
		);

		$context = $loader
			->load($this->cache->newCache("context"))
			->getContext();

		if( ! $context )
		{
			throw new NotFoundException("System context not found");
		}

		$event = new Events\ContextEvent($context, $loader->getCollection());
		$this->event->dispatch($event);

		$this->ci["context"] = $event->getParam("context");
		$this->context = $this->ci["context"];
		return $this->ci["context"];
	}

	/**
	 * Check if the system has been installed.
	 *
	 * @return bool
	 *
	 * @throws \Throwable
	 */
	public function installed(): bool
	{
		return (bool) $this->system("install", false);
	}

	public function run(): string
	{
		static $is_run = false, $result = 'html';

		if( $is_run )
		{
			return $result;
		}

		$is_run = true;

		// system shutdown

		register_shutdown_function(function() {
			$this->close();
		});

		$this->init();

		// load system config
		// check install

		if( $this->installed() )
		{
			$status = $this->system("status");
			if( strpos($status, "-progress") > 0 || $status === "progress" )
			{
				throw new Exceptions\UnavailableException("Web site is temporarily unavailable");
			}

			if( $this->host->getDebugMode() !== "production" )
			{
				@ ini_set( "display_errors", "on" );
				error_reporting( E_ALL );
			}

			// php init values

			$iniSet = $this->system("iniSet");
			if( is_array($iniSet) )
			{
				// etc. ini_set('html_errors', 'on');
				foreach($iniSet as $name => $value )
				{
					ini_set($name, $value);
				}
			}

			// run boot config (only for host)

			$file = Path::application("boot.php");
			file_exists($file) && Callback::tap(static function($file, $app) { include $file; }, $file, $this);
			$this->event->dispatch(new Events\BootEvent());
		}

		// run
		// console or web mode

		$result = SERVER_CLI_MODE ? $this->runCli() : $this->runWeb();
		return $result;
	}

	/**
	 * Load Controller
	 *
	 * @param ControllerInterface $controller
	 *
	 * @return ControllerInterface
	 *
	 * @throws Exceptions\AccessException
	 */
	public function changeController( ControllerInterface $controller ): ControllerInterface
	{
		if( isset( $this->ci["controller"] ) && !$this->controller->changeable( $controller ) )
		{
			throw new Exceptions\AccessException("Current controller '" . get_class( $this->ci["controller"] ) . "' does not allow change");
		}

		unset($this->ci["controller"]);
		$this->ci["controller"] = $controller;
		$this->controller = $controller;

		return $this->controller;
	}

	/**
	 * @return string
	 *
	 * @throws Exception
	 * @throws Exceptions\NotFoundException
	 * @throws Exceptions\ReadException
	 * @throws ReflectionException
	 */
	private function runCli(): string
	{
		if( ! class_exists(Application::class, true) )
		{
			throw new Exceptions\RuntimeException("Symfony console package is not installed");
		}

		$term = new Terminal();
		$term->run();

		return 'cli';
	}

	/**
	 * @return string
	 *
	 * @throws \Throwable
	 */
	private function runWeb(): string
	{
		// web access

		if( !$this->installed() )
		{
			$host = $this->host;

			if($host->isRedirect())
			{
				// redirect to host
				$this
					->response
					->redirect( $host->getRedirectUrl() )
					->send();

				return 'redirect';
			}
			else if($host->isDefined())
			{
				throw new Exceptions\RuntimeException("System is not install for this domain");
			}
			else
			{
				throw new Exceptions\RuntimeException("The selected domain is not installed or the configuration file is not specified hostname");
			}
		}

		// load manifest data

		$url = $this->url;
		$mnf = Prop::prop('manifest');
		$response = $this->response;

		$url->reloadRequest( $this->host->getOriginalHost() );

		if( $mnf->count() && $url->getMode() == 'rewrite' && $url->count() > 0 && $mnf->has( $url->getPath() ) )
		{
			$path = $url->getPath();
			$data = Data::value( $mnf->get($path) );

			if( is_string($data) )
			{
				$data = ['content' => $data];
			}

			return $this->renderManifest($path, $data);
		}

		if( $this->system("status") === "offline" )
		{
			$event = new Events\WebsiteOfflineEvent();
			$this->event->dispatch($event);

			if( !$event->isPropagationStopped() )
			{
				throw new Exceptions\UnavailableException("Web site is temporarily unavailable");
			}
		}

		$open = $url->count() > 0 && ! $url->isDir();

		// load or create context

		$context = $this->loadContext();
		$prop = new Prop($context->getProperties());

		// shift URL path

		if( $context->isPath() )
		{
			$path = $context->getPath();

			// redirect to folder
			if( $open && ("/" . $path) == $url->getPath() )
			{
				$response
					->redirect( $url->makeUrl( $url->getPath() . "/" ) )
					->send();

				return 'redirect';
			}

			$url->shift(substr_count($path, "/") + 1);
		}

		// update system language

		if( $prop->has( "language" ) )
		{
			$language = $prop->get("language");
			if( $this->loaded("language") )
			{
				$this->lang->reload($language);
			}
			else
			{
				$this->event->listen(LanguageEvent::eventName(), function(LanguageEvent $event) use ($language) {
					if( $event instanceof ReadyLanguageEvent ) {
						$event->setParam("language", $language);
					}
				});
			}
		}

		// load default page

		$this->event->dispatch(new Events\LoadEvent());

		// load routers array

		$cache = $this->cache->newCache('routers');
		if( $cache->ready() )
		{
			$routers = $cache->import();
			$isRoute = count($routers) > 0;
		}
		else {
			$routers = Routers_SchemeDesigner::find()
				->get()
				->map(function(Routers_SchemeDesigner $schemeDesigner) {
					return new MountPoint($schemeDesigner->getId(), $schemeDesigner->getModuleId(), $schemeDesigner->getPath(), $schemeDesigner->getProperties());
				})
				->getAll();

			$isRoute = count($routers) > 0;
			if( $isRoute )
			{
				$cache->export($routers);
			}
		}

		$mountPoint404 = false;
		$page404 = false;

		/** @var \RozaVerta\CmfCore\Route\Interfaces\ControllerInterface|null $controller */

		$controller = null;
		$found = false;

		// math

		if( $isRoute )
		{
			$isHomePage = $url->count() < 1;
			$pagePath = $url->getPath();
			$pagePathClose = $url->isDir() ? false : ($pagePath . "/");

			/** @var MountPoint $mountPoint */

			foreach( $routers as $mountPoint )
			{
				// if context not use this module
				if( !$context->hasMountPoint($mountPoint) )
				{
					continue;
				}

				$math = false;

				// if controller not found
				if( $mountPoint->is404() )
				{
					$mountPoint404 = $mountPoint;
					if( $found )
					{
						break;
					}
				}
				else if( ! $found )
				{
					if( $mountPoint->isHomePage() )
					{
						$math = $isHomePage;
					}
					else if($mountPoint->isBasePath())
					{
						$math = true;
					}
					else if(! $mountPoint->isClose())
					{
						$math = !$url->isDir() && $pagePath === $mountPoint->getPath();
					}
					else
					{
						$mountPath = $mountPoint->getPath();
						$mountLength = strlen($mountPath);
						if( strlen($pagePath) <= $mountLength && substr($pagePath, 0, $mountLength) === $mountPath )
						{
							$math = true;
						}
						else if($mountPath === $pagePathClose)
						{
							// redirect to folder

							$response
								->redirect($url->makeUrl($mountPath, $_GET ?? [], true))
								->send();

							return 'redirect';
						}
					}
				}

				if( $math )
				{
					$controller = $this->initializeController( $mountPoint );

					// found
					if( $controller !== null )
					{
						$found = true;
						if( $mountPoint404 )
						{
							break;
						}
					}
				}
			}

			if( $controller === null && $mountPoint404 )
			{
				$controller = $this->initializeController( $mountPoint404 );
				$page404 = true;
			}
		}

		if( $controller === null )
		{
			if( $isRoute )
			{
				throw new PageNotFoundException;
			}
			else
			{
				$controller = new Welcome(new MountPoint(1, 1, "/"));
			}
		}

		$this->changeController($controller);

		$initialize = $this->controller->initialize();
		if( !$initialize && !$page404 && $mountPoint404 )
		{
			$controller = $this->initializeController( $mountPoint404 );
			if( $controller !== null )
			{
				$this->changeController($controller);
				$initialize = $this->controller->initialize();
			}
		}

		if( !$initialize )
		{
			throw new Exceptions\RuntimeException("Can't load route settings");
		}

		$cacheable = $this->controller->isCacheable();

		if($cacheable)
		{
			if( $this->controller instanceof ControllerContentOutputInterface || ! $this->controller->getId() )
			{
				$cacheable = false;
			}
			else
			{
				$pageCache = new PageCache($this->controller);
				if( $pageCache->ready() )
				{
					return $this->renderCache($pageCache);
				}
			}
		}

		$this->event->dispatch(new Events\ReadyEvent());

		// custom output data
		if( ! $cacheable )
		{
			$controller = $this->controller;
			if($controller instanceof ControllerContentOutputInterface && $controller->isRaw())
			{
				$controller->complete();

				$this->event->dispatch(new Events\BeforeContentOutputEvent( $controller->getContentType(), false, false ));

				$controller->output();

				// send content
				if( !$response->isSent() )
				{
					$response->send();
				}

				return 'raw';
			}
		}

		return $this->renderController(true, $cacheable, $pageCache ?? null);
	}

	/**
	 * @param bool $beforeEvent
	 * @param bool $cacheable
	 * @param PageCache|null $pageCache
	 *
	 * @return string
	 *
	 * @throws Exceptions\WriteException
	 * @throws NotFoundException
	 * @throws \Throwable
	 */
	private function renderController(bool $beforeEvent, bool $cacheable = false, ?PageCache $pageCache = null): string
	{
		$response = $this->response;
		$contentType = $response->headers()->get("Content-Type");

		if( !$contentType )
		{
			$response->header("Content-Type", "text/html; charset=" . Str::encoding());
			$contentType = 'text/html';
		}
		else
		{
			$contentType = preg_split('/[;,\s]+/', ltrim($contentType));
			$contentType = rtrim(strtolower($contentType[0]));
			if( !strlen($contentType) )
			{
				$contentType = 'unknown';
			}
		}

		$this->controller->complete();

		$view = $this->view;
		$data = $this->controller->getPageData();
		$package = "main";
		$template = "main";

		if( $this->controller instanceof ControllerPackageInterface )
		{
			$node = $this->controller->getPackageName();
			if( $node )
			{
				$package = $node;
			}
		}

		if( $this->controller instanceof ControllerTemplateInterface )
		{
			$node = $this->controller->getTemplateName();
			if( $node )
			{
				$template = $node;
			}
		}

		$ct         = $this->controller->getProperties();
		$ct['id']   = $this->controller->getId();
		$ct['name'] = $this->controller->getName();

		$view
			->mergeConfig( [
				"controller" => $ct,
				"package" => $package,
				"template" => $template,
			] )
			->usePackage( $package )
			->set( $data );

		if( $beforeEvent )
		{
			$event = new Events\BeforeContentOutputEvent($contentType, false, $cacheable);
			$this->event->dispatch($event);
			$cacheable = $event->cacheable;
		}

		if( ! $cacheable )
		{
			unset($pageCache);
		}

		unset($event);

		$response->setBody( $view->render( $template, $pageCache ?? null ) );

		// complete dispatcher

		$this->event->dispatch(new Events\CompleteEvent($contentType));

		// output data

		$response->send();

		// save cache

		if( isset($pageCache) )
		{
			$pageCache
				->setCode($response->getCode())
				->setHeaders($response->headers()->toArray())
				->setContentType($contentType)
				->setPackageName($view->getPackage()->getName())
				->setTemplateName($view->getTemplate()->getName())
				->save();
		}

		return 'html';
	}

	/**
	 * @param PageCache $pageCache
	 *
	 * @return string
	 *
	 * @throws Exceptions\WriteException
	 * @throws NotFoundException
	 * @throws \Throwable
	 */
	private function renderCache(PageCache $pageCache): string
	{
		$this->event->dispatch(new Events\ReadyEvent());

		$contentType = $pageCache->getContentType();

		$view = $this->view;

		$ct         = $this->controller->getProperties();
		$ct['id']   = $this->controller->getId();
		$ct['name'] = $this->controller->getName();

		$view
			->usePackage( $pageCache->getPackageName() )
			->set( $pageCache->getPageData() )
			->set( 'controller', $ct )
			->set( 'fromCache', true );

		$event = new Events\BeforeContentOutputEvent($contentType, true, true);
		$this->event->dispatch($event);

		if( ! $event->cacheable )
		{
			$pageCache->forget();
			return $this->renderController(false);
		}

		unset($event);

		$response = $this->response;

		foreach($pageCache->getHeaders() as $key => $value)
		{
			$response->header($key, $value);
		}

		$response
			->setCode( $pageCache->getStatusCode() )
			->setBody( $view->render( $pageCache->getTemplateName(), $pageCache ));

		// complete dispatcher

		$this->event->dispatch(new Events\CompleteEvent($contentType));

		// output data

		$response->send();

		return 'html';
	}

	/**
	 * Render static manifest file.
	 *
	 * @param string $path
	 * @param array  $data
	 *
	 * @return string
	 *
	 * @throws \Throwable
	 */
	private function renderManifest( string $path, array $data ): string
	{
		$mnf = new Prop($data);
		$response = $this->response;

		// headers
		if( $mnf->isArray('headers') )
		{
			foreach($mnf->get('headers') as $name => $header)
			{
				if( is_int($name) )
				{
					$response->header($header);
				}
				else
				{
					$response->header($name, $header);
				}
			}
		}
		else if( $mnf->has( 'header' ) )
		{
			$response->header($mnf->get('header'));
		}

		// add cache header
		// cache or no cache ?
		if( $mnf->equiv('noCache', true) )
		{
			$response->noCache();
		}
		else
		{
			$response->cache( $mnf->get( 'cache' ) );
		}

		// content
		if( $mnf->has( 'content' ) )
		{
			$response->setBody($mnf->get('content'));
		}
		else
		{
			$file = Path::application( $mnf->get( 'file', $path ) );
			if( file_exists($file) )
			{
				$response->file($file);
			}
		}

		if( !$response->isSent() )
		{
			$response->send();
		}

		return 'raw';
	}

	/**
	 * @throws Exceptions\WriteException
	 * @throws Module\Exceptions\ResourceReadException
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function close(): void
	{
		static $close = false;
		if( $close )
		{
			return;
		}

		$close = true;

		if( $this->installed() )
		{
			// rollback database transaction
			foreach( $this->database->getActiveConnections() as $conn )
			{
				if( $conn->isTransactionActive() )
				{
					$conn->rollBack();
				}
			}

			$err = error_get_last();
			if( is_array( $err ) && $err['type'] != E_NOTICE && $err['type'] != E_USER_NOTICE )
			{
				$this->log->line(
					"PHP shutdown error: " . trim( $err['message'] ) .
					", file: " . $err['file'] .
					", line: " . $err['line']
				);
			}

			// write logs
			if( $this->loaded( 'log' ) )
			{
				$this->log->flush();
			}
		}

		// shutdown callback
		$this->initialized() && $this->event->dispatch( new Events\ShutdownEvent() );
	}

	public function __get( $name )
	{
		return $this->service( $name );
	}

	public function __destruct()
	{
		$this->close();
	}

	/**
	 * Add new service object or singleton initializer.
	 *
	 * @param string $name
	 * @param $object
	 *
	 * @return $this
	 *
	 * @throws Exceptions\WriteException
	 * @throws Module\Exceptions\ResourceReadException
	 * @throws NotFoundException
	 */
	public function singleton( string $name, $object )
	{
		static $reserved = ['context', 'controller'];

		$this->init();

		if( in_array($name, $reserved) || isset($this->singletons[$name]) || isset($this->ci[$name]) )
		{
			throw new InvalidArgumentException("Duplicated class name '{$name}' for singleton instance object");
		}

		if( is_string($object) || $object instanceof Closure )
		{
			$this->singletons[$name] = $object;
		}
		else if( is_object($object) )
		{
			$this->ci[$name] = $object;
		}
		else
		{
			throw new InvalidArgumentException("Invalid object type for the '{$name}' singleton object");
		}

		return $this;
	}

	// private

	/**
	 * @throws Module\Exceptions\ResourceReadException
	 * @throws RuntimeException
	 */
	private function loadSystem()
	{
		if( $this->host->isDefined() )
		{
			$this->hostDefined = true;
			$system = Prop::file("system");
		}
		else
		{
			$system = [];
		}

		if( ! isset($system["install"]) )
		{
			$system["install"] = false;
		}

		if( ! isset($system["status"]) )
		{
			$system["status"] = $system["install"] ? "install" : "wait";
		}

		if( ! isset($system["name"]) )
		{
			try {
				$manifest = new Manifest();
			}
			catch( Exception $e ) {
				throw new RuntimeException("System load failure", $e);
			}

			$system["name"] = $manifest->getTitle();
			$system["version"] = $manifest->getVersion();
		}

		$this->system = $system;
	}

	private function initializeController( MountPointInterface $mountPoint )
	{
		$className = $mountPoint->getModule()->getNamespaceName() . 'Router';

		/** @var \RozaVerta\CmfCore\Route\Router $router */

		$router = new $className( $mountPoint );

		if( $router instanceof RouterInterface )
		{
			if( $router->initialize() )
			{
				$controller = $router->getController();
				if( ! is_object($controller) )
				{
					throw new Exceptions\RuntimeException("Router controller method mast be return controller object");
				}

				if( $controller instanceof ControllerInterface )
				{
					return $controller;
				}

				throw new Exceptions\ImplementsException("Controller must be inherited of " . ControllerInterface::class);
			}
		}
		else
		{
			throw new Exceptions\ImplementsException( "Router must be inherited of " . RouterInterface::class );
		}

		return null;
	}
}