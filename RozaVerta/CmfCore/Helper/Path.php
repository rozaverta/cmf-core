<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 21:31
 */

namespace RozaVerta\CmfCore\Helper;

use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Module\Module;

/**
 * Class Path
 * @package RozaVerta\CmfCore\Helper
 */
final class Path
{
	private function __construct()
	{
	}

	// APP_BASE_PATH, APP_CORE_PATH, APP_PATH, APP_WEB_PATH

	/**
	 * @param string $path
	 * @return mixed
	 */
	public static function getIncludeData( string $path )
	{
		return require $path;
	}

	/**
	 * @param string $path
	 * @param array $extract
	 * @return mixed
	 */
	public static function includeFile( string $path, array $extract = [] )
	{
		if( count($extract) )
		{
			extract($extract);
		}

		require $path;

		return null;
	}

	public static function path( string $path, array $replacement = [] ): string
	{
		if( $path[0] === "@" )
		{
			if(preg_match('/@([a-zA-Z]+)[ :](.*?)$/', $path, $m ))
			{
				$prefix = $m[1];
				$path = ltrim($m[2]);
			}
			else
			{
				$prefix = substr($path, 1);
				$path = "";
			}

			$prefix = strtolower($prefix);
			if( isset($replacement[$prefix]) )
			{
				$path = rtrim($replacement[$prefix], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
			}
			else
			{
				switch( $prefix )
				{
					case "app":
					case "application":
						return self::application($path);

					case "core":
						return self::core($path);

					case "root":
					case "base":
						return self::base($path);

					case "assets":
						return self::assets($path);

					case "config":
					case "cache":
					case "addons":
					case "logs":
					case "modules":
					case "resources":
					case "view":
						return self::application($path, $prefix);
				}

				return "";
			}
		}

		if( strlen($path) )
		{
			if(DIRECTORY_SEPARATOR !== "/")
			{
				$path = str_replace("/", DIRECTORY_SEPARATOR, $path );
			}
			if($path[0] !== DIRECTORY_SEPARATOR)
			{
				$path = DIRECTORY_SEPARATOR . $path;
			}
		}

		return $path;
	}

	public static function config( ?string $path = null )
	{
		return self::application( $path, "config" );
	}

	public static function cache( ?string $path = null )
	{
		return self::application( $path, "cache" );
	}

	public static function addons( ?string $path = null )
	{
		return self::application( $path, "addons" );
	}

	public static function logs( ?string $path = null )
	{
		return self::application( $path, "logs" );
	}

	public static function modules( ?string $path = null )
	{
		return self::application( $path, "modules" );
	}

	public static function resources( ?string $path = null )
	{
		return self::application( $path, "resources" );
	}

	public static function view( ?string $path = null )
	{
		return self::application( $path, "view" );
	}

	public static function application( ?string $path = null, ?string $directory = null): string
	{
		static $define = false, $prefix;

		if( ! $define )
		{
			$define = defined("APP_PATH");
			if( $define )
			{
				$prefix = APP_PATH;
			}
			else
			{
				$prefix = sys_get_temp_dir();
				if( !$prefix )
				{
					$prefix = getcwd();
				}
				$prefix .= DIRECTORY_SEPARATOR;
			}
		}

		return self::makePath($prefix, $directory, $path);
	}

	public static function core( ?string $path = null, ?string $directory = null ): string
	{
		static $define = false, $prefix;

		if( ! $define )
		{
			$prefix = ($define = defined("APP_CORE_PATH")) ? APP_CORE_PATH : realpath( __DIR__ . "/../" ) . DIRECTORY_SEPARATOR;
		}

		return self::makePath($prefix, $directory, $path);
	}

	public static function base( ?string $path = null, ?string $directory = null ): string
	{
		static $define = false, $prefix;

		if( ! $define )
		{
			$prefix = ($define = defined("APP_BASE_PATH")) ? APP_BASE_PATH : getcwd() . DIRECTORY_SEPARATOR;
		}

		return self::makePath($prefix, $directory, $path);
	}

	public static function assets( ?string $path = null, ?string $directory = null ): string
	{
		static $define = false, $prefix;

		if( ! $define )
		{
			$prefix = ($define = defined("APP_ASSETS_PATH")) ? APP_ASSETS_PATH : getcwd() . DIRECTORY_SEPARATOR . "assets" . DIRECTORY_SEPARATOR;
		}

		return self::makePath($prefix, $directory, $path);
	}

	public static function assetsWeb( ?string $path = null, ?string $directory = null ): string
	{
		static $define = false, $prefix;

		if( ! $define )
		{
			$prefix = ($define = defined("APP_ASSETS_WEB_PATH")) ? APP_ASSETS_WEB_PATH : "/assets/";
		}

		$assets = $prefix;

		if( ! is_null($directory) )
		{
			$directory = trim($directory, "/");
			if(strlen($directory))
			{
				$assets .= $directory . "/";
			}
		}

		if( ! is_null($path) )
		{
			$assets .= ltrim($path, "/");
		}

		return $assets;
	}

	public static function module(ModuleInterface $module, ?string $path = null, ?string $directory = null): string
	{
		return self::makePath($module->getPathname(), $directory, $path);
	}

	private static function makePath( $prefix, $directory, $path ): string
	{
		$directory = is_null($directory) ? "" : trim($directory, "/");
		$path = is_null($path) ? "" : ltrim($path, "/");

		if( strlen($directory) )
		{
			$path = $directory . DIRECTORY_SEPARATOR . $path;
		}

		if( strlen($path) && DIRECTORY_SEPARATOR !== "/" )
		{
			$path = str_replace("/", DIRECTORY_SEPARATOR, $path );
		}

		return ( strlen($prefix) ? $prefix : DIRECTORY_SEPARATOR ) . $path;
	}
}