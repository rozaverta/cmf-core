<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 16:37
 */

namespace RozaVerta\CmfCore\View;

use Closure;
use ReflectionClass;
use ReflectionException;
use RozaVerta\CmfCore\Exceptions\RuntimeException;
use RozaVerta\CmfCore\Exceptions\WriteException;
use RozaVerta\CmfCore\Helper\Arr;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Helper\Str;
use RozaVerta\CmfCore\Schemes\TemplatePlugins_SchemeDesigner;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\ServiceTrait;
use RozaVerta\CmfCore\View\Events\CompleteRenderEvent;
use RozaVerta\CmfCore\View\Events\PreRenderEvent;
use RozaVerta\CmfCore\View\Events\RenderGetterEvent;
use RozaVerta\CmfCore\View\Interfaces\ExtenderInterface;
use RozaVerta\CmfCore\View\Interfaces\PluginDynamicInterface;
use RozaVerta\CmfCore\View\Interfaces\PluginInterface;

/**
 * Class View
 *
 * @package RozaVerta\CmfCore\View
 */
final class View extends Lexer
{
	use ServiceTrait;

	protected $extends = [];

	private $delays = [];
	private $depth = 0;
	private $iteration_limit = 10;

	private $config = [];

	private $configCache = [];

	/**
	 * @var PluginNode[]
	 */
	private $pluginParts = [];
	private $plugin_index = 0;
	private $pluginIndex = 0;
	private $short_tags = [];

	private $http = '/^(?:\/\/|https?:)/';
	private $charset;

	/**
	 * @var Package
	 */
	private $package;

	/**
	 * @var null | Template
	 */
	private $template = null;

	private $packages = [];

	private $call = [];

	private $cacheable = false;

	private $fromCache = false;

	private $toCache = false;

	/**
	 * @var \RozaVerta\CmfCore\Route\Url
	 */
	protected $url;

	/**
	 * @var \RozaVerta\CmfCore\Log\LogManager
	 */
	protected $log;

	/**
	 * @var \RozaVerta\CmfCore\Event\EventManager
	 */
	protected $event;

	/**
	 * @var \RozaVerta\CmfCore\Cache\CacheManager
	 */
	protected $cache;

	/**
	 * @var \RozaVerta\CmfCore\Http\Request
	 */
	protected $request;

	/**
	 * @var \RozaVerta\CmfCore\Database\Connection
	 */
	protected $db;

	/**
	 * View constructor.
	 *
	 * @param array $items
	 * @param array $config
	 *
	 * @throws NotFoundException
	 * @throws WriteException
	 * @throws \RozaVerta\CmfCore\Exceptions\ClassNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function __construct( array $items = [], array $config = [] )
	{
		parent::__construct( $items );
		$this->thisServices( "db", "log", "url", "event", "request", "cache" );

		$this->config = $config;

		if( !isset( $this->config["http"] ) ) $this->config["http"] = $this->url->getPrefix();
		if( !isset( $this->config["host"] ) ) $this->config["host"] = $this->url->getHost();
		if( !isset( $this->config["assets"] ) ) $this->config["assets"] = "/assets/";
		if( !isset( $this->config["charset"] ) ) $this->config["charset"] = Str::encoding();
	}

	static private $load = false;

	public function load()
	{
		if( self::$load )
		{
			return $this;
		}

		self::$load = true;

		// load plugins

		$cache = $this->cache->newCache("plugins", 'template');
		if( $cache->ready() )
		{
			foreach($cache->import() as $className)
			{
				Plugin::register($className);
			}
		}
		else
		{
			$all = TemplatePlugins_SchemeDesigner::find()
				->where("visible", true)
				->orderBy("name")
				->get();

			$plugins = [];

			/** @var TemplatePlugins_SchemeDesigner $item */
			foreach($all as $item)
			{
				$className = $item->getClassName();

				try
				{
					$ref = new ReflectionClass( $className );
				}
				catch( ReflectionException $e )
				{
					$this->log->line("Plugin load error, class '{$className}' not found");
					continue;
				}

				if( ! $ref->implementsInterface(PluginInterface::class) )
				{
					$this->log->line("Plugin load error, class '{$className}' must be inherited of " . PluginInterface::class);
					continue;
				}

				if( !in_array($className, $plugins, true))
				{
					$plugins[] = $className;
					Plugin::register($className);
				}
			}

			$cache->export($plugins);
		}

		return $this;
	}

	public function mergeConfig( array $config )
	{
		if( count( $config ) )
		{
			$this->config = array_merge( $this->config, $config );
			$this->configCache = [];
		}
		return $this;
	}

	public function config( string $name, $default = null )
	{
		if( $name === "*" )
		{
			return $this->config;
		}

		if( isset( $this->configCache[$name] ) )
		{
			return $this->configCache[$name];
		}

		if( strpos( $name, "." ) === false )
		{
			return array_key_exists( $name, $this->config ) ? $this->config[$name] : $default;
		}

		$keys = explode( ".", $name );
		$target = &$this->config;

		foreach( $keys as $key )
		{
			if( Arr::accessible( $target ) && Arr::exists( $target, $key ) )
			{
				$target = &$target[$key];
			}
			else if( is_object( $target ) && property_exists( $target, $key ) )
			{
				$target = &$target->{$key};
			}
			else
			{
				return $default;
			}
		}

		$this->configCache[$name] = $target;
		return $target;
	}

	/**
	 * Get view extender
	 *
	 * @param string $name
	 * @return object|UnknownExtenderProxy
	 */
	public function ext(string $name)
	{
		if( !isset($this->extends[$name]) )
		{
			return new UnknownExtenderProxy($name, $this);
		}
		else
		{
			return $this->extends[$name];
		}
	}

	/**
	 * Register new extender
	 *
	 * @param string                              $name
	 * @param string | ExtenderInterface | object $object
	 *
	 * @return $this
	 *
	 * @throws NotFoundException
	 * @throws WriteException
	 * @throws \RozaVerta\CmfCore\Exceptions\ClassNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	public function register(string $name, $object)
	{
		if( !is_object($object) )
		{
			try {
				$ref = new ReflectionClass($object);
			}
			catch( ReflectionException $e ) {
				$this->log->addError( "Cannot load extender class ({$name}). " . $e->getMessage() );
				return $this;
			}

			if( $ref->implementsInterface(ExtenderInterface::class) )
			{
				$this->log->addError( "Extender class ({$name}) must implements of " . ExtenderInterface::class );
				return $this;
			}

			$object = $ref->newInstance($name, $this);
		}

		if(isset($this->extends[$name]))
		{
			$this->log->addDebug( "Override view extender method '{$name}'" );
		}

		$this->extends[$name] = $object;

		return $this;
	}


	/**
	 * @return Package|null
	 */
	public function getPackage(): ?Package
	{
		return $this->package;
	}

	/**
	 * @return Template|null
	 */
	public function getTemplate(): ?Template
	{
		return $this->template;
	}

	// global data

	public function delay( string $name ): string
	{
		if( !isset($this->delays[$name]) )
		{
			$this->delays[$name] = md5(mt_rand());
		}
		return '{itemDelay:' . $name . ':' . $this->delays[$name] . '}';
	}

	public function request( $name, $default = '', $escape = true )
	{
		$value = $this->request->param( $name, $default );

		if( !$escape )
		{
			return $value;
		}

		if( is_array($value) )
		{
			return array_map('htmlspecialchars', $value);
		}
		else
		{
			return htmlspecialchars($value);
		}
	}

	/**
	 * Use RenderGetterEvent before return item value
	 *
	 * @example <?= $view->getOn("content") ?>
	 * @example <?= $view->getOn("pageTitle") ?>
	 *
	 * @param string $name    name or path
	 * @param string $default default result value
	 * @return mixed
	 * @throws NotFoundException
	 * @throws WriteException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function getOn( string $name, $default = "" )
	{
		$event = new RenderGetterEvent($this, $name, $this->get($name, $default));
		$this->event->dispatch($event);
		return $event->propertyValue;
	}

	// assets

	public function assets( $file, $full = false )
	{
		return ( $full ? $this->config( 'http', '' ) : '' ) . $this->config( "assets" ) . $file;
	}

	public function getScript( $files, array $prop = [] ): string
	{
		$files = $this->inkFiles( $files, $prop, "js" );

		$attr = isset( $prop["type"] ) ? ' type="' . $prop["type"] . '"' : '';

		$node = $prop["charset"] ?? false;
		if( $node )
		{
			$attr .= ' charset="' . ( $this->incAuto( $node ) ? $this->config( "charset" ) : $node ) . '"';
		}

		$node = $prop["language"] ?? false;
		if( $node )
		{
			$attr .= ' language="' . ( $this->incAuto( $node ) ? 'JavaScript' : $node ) . '"';
		}

		for( $i = 0, $len = count( $files ); $i < $len; $i++ )
		{
			$files[$i] = '<script' . $attr . ' src="' . htmlspecialchars( $files[$i]["file"] ) . '"></script>';
		}

		return $this->inc2nl( $files, $prop );
	}

	public function getStyle( $files, array $prop = [] ): string
	{
		$files = $this->inkFiles( $files, $prop, "css" );

		$attr = '';
		if( !isset($prop["type"]) ) $attr .= ' type="text/css"';
		else if( !empty($prop["type"]) ) $attr .= ' type="' . $prop["type"] . '"';

		if( !isset($prop["media"]) ) $attr .= ' media="all"';
		else if( !empty($prop["media"]) ) $attr .= ' media="' . $prop["media"] . '"';

		if( !isset($prop["rel"]) ) $attr .= ' rel="stylesheet"';
		else if( !empty($prop["rel"]) ) $attr .= ' rel="' . $prop["rel"] . '"';

		for( $i = 0, $len = count( $files ); $i < $len; $i++ )
		{
			$files[$i] = '<link' . $attr . ' href="' . htmlspecialchars( $files[$i]["file"] ) . '" />';
		}

		return $this->inc2nl( $files, $prop );
	}

	public function getImg( $files, array $prop = [] ): string
	{
		$files = $this->inkFiles( $files, $prop, "images" );
		$size = $prop["size"] ?? false;
		if( $size )
		{
			$size = $this->incAuto( $size );
		}

		for( $i = 0, $len = count( $files ); $i < $len; $i++ )
		{
			$file = $files[$i];
			$image = '<img src="' . htmlspecialchars( $file["file"] ) . '"';
			if( $size )
			{
				if( $file["local"] && $file["exists"] )
				{
					$calc = @ getimagesize( $file["path"] );
					if( $calc )
					{
						$image .= ' ' . $calc[3];
					}
				}
			}
			else
			{
				if( isset( $prop["width"] ) ) $image .= ' width="' . $prop["width"] . '"';
				if( isset( $prop["height"] ) ) $image .= ' height="' . $prop["height"] . '"';
			}
			$files[$i] = $image . ' />';
		}

		return $this->inc2nl( $files, $prop, $len > 1 );
	}

	// template

	public function usePackage( string $name )
	{
		if( $this->depth > 0 )
		{
			throw new RuntimeException("You can not change the package in the process of rendering");
		}

		if( !isset($this->packages) )
		{
			$this->packages = Prop::prop("packages");
		}

		$name = trim($name);
		$id = Package::getIdFromName($name);
		if(is_null($id))
		{
			throw new Exceptions\PackageNotFoundException("The '{$name}' package not found");
		}

		$this->package = Package::package($id);
		$this->package->loadFunctions($this);
		$this->config['assets'] = $this->package->getAssets();
		$this->config['package'] = $name;

		return $this;
	}

	/**
	 * @return int
	 */
	public function getDepth(): int
	{
		return $this->depth;
	}

	/**
	 * @return bool
	 */
	public function isToCache(): bool
	{
		return $this->toCache;
	}

	/**
	 * @return bool
	 */
	public function isFromCache(): bool
	{
		return $this->fromCache;
	}

	/**
	 * Render template content
	 *
	 * @param string $template
	 * @param PageCache|null $cache
	 * @return string
	 *
	 * @throws NotFoundException
	 * @throws WriteException
	 */
	public function render( string $template, ?PageCache $cache = null ): string
	{
		if( $this->depth > 0 )
		{
			throw new \RuntimeException("View process is run");
		}

		if( !isset( $this->package ) )
		{
			$this->usePackage( $this->config( "package", "main" ) );
		}

		$this->cacheable = ! is_null($cache);
		$this->fromCache = $this->cacheable && $cache->ready();
		$this->toCache   = $this->cacheable && ! $this->fromCache;

		$this->template = $this->package->getTemplate($template);
		$this->event->dispatch(new PreRenderEvent($this, $this->template, $this->fromCache));
		$this->depth = 1;

		$local = $this;

		if($this->fromCache)
		{
			$body = $cache->getBody();

			foreach($cache->getPlugins() as $plug)
			{
				$key = $plug->getKey();
				$pos = strpos($body, $key);

				if( $pos !== false )
				{
					$body =
						($pos > 0 ? substr($body, 0, $pos) : "") . // start
						$this->plugin($plug->getName(), $plug->getData()) . // plugin
						substr($body, $pos + strlen($key))   // end
					;
				}
			}

			$this->items = array_merge($this->items, $cache->getPageData());
		}
		else
		{
			$path = $this->template->getPathname();
			$app = self::app();
			$view = $this;

			ob_start();
			Path::includeFile($path, compact('path', 'template', 'local', 'app', 'view'));
			$body = ob_get_contents();
			ob_end_clean();
		}

		$this->depth = 0;

		// replace delay variables

		$depth = 1; // todo (int) $this->template->getOr("delayDepth", 1);
		$pluginDepth = 3; // todo (int) $this->template->getOr("pluginDepth", 1);
		$body = $this->replaceDelay($body, $depth);

		foreach( array_keys($this->pluginParts) as $key)
		{
			$plug = & $this->pluginParts[$key];
			$plug->setBody($this->replaceDelay($plug->getBody(), $depth));
			unset($plug);
		}

		$replacePluginBody = static function( PluginNode $plug, $part, $oldKey ) { return $plug->getBody(); };

		if( $this->toCache )
		{
			$plugins = [];

			$body = $this->_replacePlugin( $body, function( PluginNode $plug, $part, $oldKey ) use (& $plugins, $replacePluginBody, $pluginDepth) {

				$type = $plug->getType();
				if( $type === PluginDynamic::CACHE_PAGE )
				{
					return $plug->getBody();
				}

				if($pluginDepth > 1)
				{
					$plug->setBody(
						$this->_replacePlugin($plug->getBody(), $replacePluginBody, $pluginDepth - 1)
					);
				}

				$plugins[$part] = new PluginCacheNode($plug->getName(), $plug->getData(), $oldKey);

				return $oldKey;

			}, $pluginDepth);

			$cache
				->setPageData( $this->items )
				->setPlugins($plugins)
				->setBody($body);

			$pluginDepth = 1;
		}

		// replace plugin data
		$body = $this->_replacePlugin( $body, $replacePluginBody, $pluginDepth );

		// dispatch render complete event
		// update body
		$event = new CompleteRenderEvent($this, $this->template, $body);
		$this->event->dispatch($event);

		// remove template
		$this->template = null;

		return $event->body;
	}

	public function getChunk( string $name, array $local = [] ): string
	{
		static $parent = "", $lexer = [];

		if( $this->depth < 1 )
		{
			throw new RuntimeException("You can use chunks only in the rendering process");
		}

		if( $this->depth > $this->iteration_limit )
		{
			return "[iteration limit]";
		}

		$back = $parent;

		if($name[0] === ".")
		{
			$name = strlen($parent) ? ($parent . $name) : substr($name, 1);
		}

		$path = $this->package->getChunkFilename($name);
		if( ! $path )
		{
			return "Chunk '{$name}' not found.";
		}

		$dot = strrpos($name, ".");
		$parent = $dot === false ? "" : substr($name, 0, $dot);
		$top = $lexer[$this->depth] ?? $this;

		++ $this->depth;

		$local = new Lexer($local, $top);
		$lexer[$this->depth] = $local;

		ob_start();
		$body = Path::includeFile( $path, [ 'path' => $path, 'chunk' => $name, 'view' => $this, 'app' => self::app() ] );
		$chunk = ob_get_contents();
		ob_end_clean();

		unset($lexer[$this->depth]);

		-- $this->depth;

		if( ! is_string($body) || ! strlen($body) )
		{
			$body = $chunk === false ? "" : $chunk;
		}

		$parent = $back;

		unset( $local, $back, $chunk );

		return $body;
	}

	public function chunkExists( string $template ): bool
	{
		return isset($this->package) && $this->package->chunkExists( $template );
	}

	protected function pluginDynamic(string $name, PluginDynamicInterface $plugin, array $data): string
	{
		$render = parent::pluginDynamic($name, $plugin, $data);
		if( ! $this->cacheable || $this->fromCache )
		{
			return $render;
		}

		$type = $plugin->getCacheType();
		if($type === PluginDynamic::CACHE_PAGE )
		{
			return $render;
		}

		$number = $this->pluginIndex ++;
		$hash = md5(mt_rand( 1000, 100000 )) . "-" . time();
		$this->pluginParts[$number] = new PluginNode($name, $render, $type, $data, $hash);

		return '{plugin:' . $number . ':' . $hash . '}';
	}

	public function replaceDelay( $html, $nestingLevel = 1 )
	{
		if( !is_int($nestingLevel) )
		{
			$nestingLevel = (int) $nestingLevel;
		}

		if( $nestingLevel > 10 )
		{
			$nestingLevel = 10;
		}
		else if( $nestingLevel < 1 )
		{
			$nestingLevel = 1;
		}

		$pref = '{back_' . md5(mt_rand( 1000, 100000 )) . "_" . time() . ':';
		$failure = [];

		// max level = 3
		for( ; $nestingLevel > 0; $nestingLevel-- )
		{
			if( strpos( $html, '{itemDelay:' ) === false ) {
				break;
			}

			$html = preg_replace_callback( '/\{itemDelay:(.*?):(.*?)\}/', function($m) use ($pref, & $failure) {
				$name = $m[1];
				if( ! isset($this->delays[$name]) || $this->delays[$name] !== $m[2])
				{
					$key = $pref . count($failure) . '}';
					$failure[$key] = $m[0];
					return $key;
				}
				else
				{
					return $this->get($name, "");
				}
			}, $html );
		}

		if(count($failure))
		{
			$html = str_replace(array_keys($failure), array_values($failure), $html);
		}

		return $html;
	}

	// private

	private function _replacePlugin( string $html, Closure $callBack, int $depth = 1 )
	{
		$pref = '{back_' . md5(mt_rand( 1000, 100000 )) . "_" . time() . ':';
		$save = [];

		$replace = function ( $m ) use ( $callBack, $pref, & $save )
		{
			$id = (int) $m[1];
			$key = $pref . count($save) . '}';
			$pluginKey = $m[0];

			if( isset( $this->pluginParts[$id] ) && $m[2] === $this->pluginParts[$id]->getHash() )
			{
				$save[$key] = $callBack( $this->pluginParts[$id], $id, $pluginKey );
			}
			else
			{
				$save[$key] = $pluginKey;
			}

			return $key;
		};

		if( $depth > 10 )
		{
			$depth = 10;
		}
		else if( $depth < 1 )
		{
			$depth = 1;
		}

		// max level = 3
		for( ; $depth > 0; $depth-- )
		{
			if( strpos( $html, '{plugin:' ) !== false )
			{
				$html = preg_replace_callback( '/\{plugin:(\d+):(.*?)\}/', $replace, $html, -1, $count );
				if($count < 1)
				{
					break;
				}
			}
		}

		if(count($save))
		{
			$html = str_replace(array_keys($save), array_values($save), $html);
		}

		return $html;
	}

	protected function normalizePath( string $url ): string
	{
		for(;;)
		{
			$eot = strpos( $url, '/../' );
			if( $eot )
			{
				$pos = strrpos( $url, "/", $eot - strlen($url) - 1 );
				if( $pos !== false )
				{
					$url = substr_replace( $url, '/', $pos, $eot - $pos + 4 );
					continue;
				}
			}
			break;
		}

		return $url;
	}

	protected function incAuto( $value ): bool
	{
		return $value === true || $value && Str::lower( (string) $value ) === "auto";
	}

	protected function inkFiles( $files, array $prop, string $dir ): array
	{
		if( isset( $prop["dir"] ) )
		{
			$dir = trim( $prop["dir"], "/" );
		}

		$src = $this->config( "assets" );
		if( $dir )
		{
			$src .= $dir . "/";
			$dir .= DIRECTORY_SEPARATOR;
		}

		if( isset( $prop['full'] ) && $prop['full'] )
		{
			$src = $this->config( 'http', '' ) . $src;
		}

		$ver = $prop["version"] ?? false;
		$srv = $this->incAuto( $ver );
		$prf = $this->package->getAssetsPath();

		if( !is_array( $files ) )
		{
			$files = [ $files ];
			$len = 1;
		}
		else
		{
			$len = count( $files );
		}

		for( $i = 0; $i < $len; $i++ )
		{
			$file = (string) $files[$i];
			if( $file[0] === "/" || preg_match( $this->http, $file ) )
			{
				$files[$i] = [
					"local" => false,
					"file" => $file,
				];
			}
			else
			{
				$path = $prf . $dir . $file;
				$file = $this->normalizePath( $src . $file );
				$exists = file_exists( $path );

				if( $ver )
				{
					if( !$srv )
					{
						$file .= "?v=" . $ver;
					}
					else if( $exists )
					{
						$time = @ filemtime( $file );
						if( $time )
						{
							$file .= '?t=' . $time;
						}
					}
				}

				$files[$i] = [
					"local" => true,
					"exists" => $exists,
					"file" => $file,
					"path" => $path,
				];
			}
		}

		return $files;
	}

	protected function inc2nl( array $files, array $prop, bool $el2nl = true ): string
	{
		$nln = $prop["nl"] ?? "\n";
		return ( $prop["fl"] ?? "" ) . implode( $nln, $files ) . ( $prop["el"] ?? ( $el2nl ? $nln : "" ) );
	}
}