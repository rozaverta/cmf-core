<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2015
 * Time: 0:52
 */

namespace RozaVerta\CmfCore\View;

use ReflectionClass;
use ReflectionException;
use RozaVerta\CmfCore\Cache\CacheManager;
use RozaVerta\CmfCore\Event\EventManager;
use RozaVerta\CmfCore\Events\ShutdownEvent;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Module\Module;
use RozaVerta\CmfCore\Module\ModuleHelper;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\View\Interfaces\LexerInterface;
use RozaVerta\CmfCore\View\Interfaces\PluginInterface;

abstract class Plugin implements PluginInterface
{
	use ModuleGetterTrait;

	protected $lexer;

	public function __construct( ModuleInterface $module, LexerInterface $lexer )
	{
		if( strpos(get_class($this), $module->getNamespaceName()) !== 0 )
		{
			throw new \InvalidArgumentException("Invalid current module");
		}

		$this->setModule($module);
		$this->lexer = $lexer;
	}

	// static

	static private $plugins = [];

	static public function plugin( $name, LexerInterface $lexer): PluginInterface
	{
		if( ! isset(self::$plugins[$name]) )
		{
			throw new Exceptions\PluginNotFoundException( "The \"{$name}\" plugin not found." );
		}

		$plug = self::$plugins[$name];
		return new $plug["className"](Module::module($plug["moduleId"]), $lexer);
	}

	static public function exists( $name)
	{
		return isset(self::$plugins[$name]);
	}

	static public function register( string $className )
	{
		static $listen = false, $plug, $cache;

		if( !isset($plug) )
		{
			$cache = CacheManager::getInstance()->newCache("plugin_registered", "template");
			$plug = $cache->ready() ? $cache->import() : [];
		}

		if( isset($plug[$className]) )
		{
			$plugins[$plug[$className]["name"]] = $plug[$className];
			return;
		}

		try {
			$ref = new ReflectionClass( $className );
		} catch( ReflectionException $e )
		{
			throw new Exceptions\PluginNotFoundException( "The \"{$className}\" plugin class not found.", $e );
		}

		if( ! $ref->implementsInterface(PluginInterface::class) )
		{
			throw new \Exception(); // todo
		}

		try {
			$name = $ref->getMethod( "getPluginName" )->invoke( null );
		} catch( ReflectionException $e )
		{
			throw new \Exception(); // todo
		}

		if( self::exists($name) )
		{
			throw new \InvalidArgumentException( "The \"{$name}\" plugin already exists." );
		}

		if( !$listen )
		{
			$listen = true;
			EventManager::getInstance()->listen(ShutdownEvent::eventName(), function() use ($cache, & $plug) {
				$cache->export($plug);
			});
		}

		$moduleId = ModuleHelper::getId($className);
		if( is_null($moduleId) )
		{
			throw new \InvalidArgumentException(); // TODO
		}

		$plug[$className] = compact('name', 'className', 'moduleId');
		self::$plugins[$name] = $plug[$className];
	}
}