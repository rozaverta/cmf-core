<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.08.2018
 * Time: 11:09
 */

namespace RozaVerta\CmfCore\Host;

use RozaVerta\CmfCore\Filesystem\Exceptions\FileNotFoundException;
use RozaVerta\CmfCore\Helper\Callback;
use InvalidArgumentException;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;

/**
 * Class HostManager
 *
 * @method static HostManager getInstance()
 *
 * @package RozaVerta\CmfCore\Host
 */
final class HostManager
{
	use SingletonInstanceTrait;

	/**
	 * @var Host
	 */
	private $originalHost = null;

	/**
	 * @var string
	 */
	private $originalName = "";

	/**
	 * @var string
	 */
	private $name = "localhost";

	/**
	 * @var string
	 */
	private $applicationPathname = "";

	/**
	 * @var string
	 */
	private $assetsPathname = "";

	/**
	 * @var string
	 */
	private $assetsWebPathname = "";

	/**
	 * @var bool
	 */
	private $ssl = false;

	/**
	 * @var int
	 */
	private $port = 80;

	/**
	 * @var string
	 */
	private $debugMode = "production";

	/**
	 * @var string
	 */
	private $file = "";

	/**
	 * @var string
	 */
	private $encoding = "UTF-8";

	/**
	 * @var bool
	 */
	private $redirect = false;

	/**
	 * @var bool
	 */
	private $loaded = false;

	/**
	 * @var string
	 */
	private $redirectUrl = "";

	/**
	 * @var array
	 */
	private $_conf = [
		'hosts'     => [],
		'www'       => [],
		'aliases'   => [],
		'ssl'       => [],
		'redirect'  => []
	];

	public function isReferer(): bool
	{
		if( SERVER_CLI_MODE )
		{
			return false;
		}

		if( isset($_SERVER['HTTP_REFERER'], $_SERVER["HTTP_HOST"]) )
		{
			$ref = ($this->isServerSsl() ? "https://" : "http://") . $this->originalHostName();
			if( $ref === $_SERVER['HTTP_REFERER'] || strpos($_SERVER['HTTP_REFERER'], $ref . "/") === 0 )
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->ssl;
	}

	/**
	 * @return bool
	 */
	public function isServerSsl(): bool
	{
		return ( isset( $_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' || isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https' );
	}

	public function getPort(): int
	{
		return $this->port;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getOriginalName(): string
	{
		return $this->originalName;
	}

	public function getApplicationPathname(): string
	{
		return $this->applicationPathname;
	}

	public function getAssetsPathname(): string
	{
		return $this->assetsPathname;
	}

	public function getAssetsWebPathname(): string
	{
		return $this->assetsWebPathname;
	}

	public function getDebugMode(): string
	{
		return $this->debugMode;
	}

	/**
	 * @return string
	 */
	public function getEncoding(): string
	{
		return $this->encoding;
	}

	/**
	 * @return string
	 */
	public function getFile(): string
	{
		return $this->file;
	}

	/**
	 * @return bool
	 */
	public function isRedirect(): bool
	{
		return $this->redirect;
	}

	/**
	 * @return string
	 */
	public function getRedirectUrl(): string
	{
		return $this->redirectUrl;
	}

	/**
	 * @return bool
	 */
	public function isDefined(): bool
	{
		return defined("APP_HOST");
	}

	/**
	 * @return bool
	 */
	public function isLoaded(): bool
	{
		return $this->loaded;
	}

	/**
	 * @param string|null $host
	 * @return bool
	 * @throws FileNotFoundException
	 */
	public function reload( ?string $host = null ): bool
	{
		if( $this->isDefined() )
		{
			throw new InvalidArgumentException("You cannot reload host after defining the constants");
		}

		$this->loaded = false;
		$this->redirect = false;

		$this->loadHostFile();

		$port = 80;
		$ssl = false;

		if( is_string($host) && strlen($host) )
		{
			if( preg_match('/^https?:\/\//', $host, $m) )
			{
				$ssl = $m[0] === 'https://';
				$host = substr($host, strlen($m[0]));
			}

			if( preg_match('/:(\d{1,4})\/?$/', $host, $m) )
			{
				$port = (int) $m[1];
				$host = substr($host, 0, strlen($host) - strlen($m[0]));
			}

			if( !filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) )
			{
				throw new InvalidArgumentException("Invalid host name '{$host}'");
			}
		}
		else
		{
			$host = $this->originalHostName();

			if( ! SERVER_CLI_MODE )
			{
				$ssl = $this->isServerSsl();
			}

			if( isset($_SERVER['SERVER_PORT']) && is_numeric($_SERVER['SERVER_PORT']) )
			{
				$port = (int) $_SERVER['SERVER_PORT'];
			}
		}

		$this->originalName = $host;
		$this->name = $host;
		$this->ssl = $ssl;
		$this->port = $port;

		// found host file

		// www
		if( strlen($host) > 4 && substr($host, 0, 4) === "www." )
		{
			$base_host = substr($host, 4);
			if( in_array($base_host, $this->_conf['www'], true) )
			{
				$host = $base_host;
			}
		}

		// aliases
		if( isset($this->_conf['aliases'][$host]) )
		{
			$host = $this->_conf['aliases'][$host];
		}

		// https redirect
		if( ! $this->ssl && in_array($host, $this->_conf['ssl'], true) )
		{
			$this->setRedirect( "https://" . $this->originalName );
			return false;
		}

		// redirect
		if( isset($this->_conf['redirect'][$host]) )
		{
			$this->setRedirect( ($this->ssl ? "https": "http") . "://" . $this->_conf['redirect'][$host] );
			return false;
		}

		$pref = $this->ssl ? "https://" : "http://";
		$suffix = ':' . $this->port;

		$this->loaded =
			$this->found(($pref . $host . $suffix), $host) ||
			$this->found(($pref . $host), $host) ||
			$this->found(($host . $suffix), $host) ||
			$this->found($host, $host)
		;

		return $this->loaded;
	}

	public function define()
	{
		if( ! $this->isLoaded() )
		{
			throw new InvalidArgumentException("The selected domain is not installed or the configuration file is not specified current domain name");
		}

		if( ! $this->isDefined() )
		{
			define( "APP_HOST"              , $this->getName() );
			define( "APP_HOST_REFERER"      , $this->isReferer() );
			define( "APP_ORIGINAL_HOST"     , $this->getOriginalName() );
			define( "APP_PATH"              , $this->getApplicationPathname() );
			define( "APP_ASSETS_PATH"       , $this->getAssetsPathname() );
			define( "APP_ASSETS_WEB_PATH"   , $this->getAssetsWebPathname() );

			defined("APP_ENCODING")   || define("APP_ENCODING"      , $this->encoding );
			defined("APP_PROTOCOL")   || define("APP_PROTOCOL"      , $this->ssl ? 'https' : 'http');
			defined("APP_DEBUG_MODE") || define("APP_DEBUG_MODE"    , $this->getDebugMode());
		}
	}

	public function getOriginalHost(): Host
	{
		if( ! $this->originalHost )
		{
			$this->originalHost = new Host(
				($this->isSsl() ? "https://" : "http://") . $this->getOriginalName() . ':' . $this->getPort()
			);
		}
		return $this->originalHost;
	}

	private function found(string $found, string $host): bool
	{
		if( !array_key_exists($found, $this->_conf['hosts']) )
		{
			return false;
		}

		$config = $this->_conf['hosts'][$found];
		if( ! isset($config["application_path"]) ) $config["application_path"] = "application";
		if( ! isset($config["assets_path"]) ) $config["assets_path"] = "assets";

		$this->name = $host;
		$this->applicationPathname = $this->separator($config["application_path"]);
		$this->assetsPathname = $this->separator($config["assets_path"]);
		$this->assetsWebPathname = ( isset($config["assets_web_path"]) && $config["assets_web_path"] != null ? rtrim($config["assets_web_path"], "/") : ("/" . $config["assets_path"]) ) . "/";
		$this->debugMode = $config["debug_mode"] ?? "production";
		$this->encoding = $config["encoding"] ?? "UTF-8";

		return true;
	}

	private function originalHostName()
	{
		static $cmd_found = false;

		if( SERVER_CLI_MODE )
		{
			$host = empty($_SERVER['HTTP_HOST']) ? 'unknown.local' : $_SERVER['HTTP_HOST'];

			$argv = isset($_SERVER["argv"]) && is_array($_SERVER["argv"]) ? $_SERVER["argv"] : [];
			$index = array_search("--host", $argv );

			if( $index !== false && isset($argv[++$index]) && filter_var($argv[$index], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) )
			{
				$host = $argv[$index];
				$cmd_found = $host;

				array_splice($_SERVER["argv"], $index-1, 2);
			}
			else if($cmd_found)
			{
				return $cmd_found;
			}

			return $host;
		}
		else
		{
			return empty($_SERVER['HTTP_HOST']) ? 'localhost' : $_SERVER['HTTP_HOST'];
		}
	}

	private function loadHostFile()
	{
		if( $this->file )
		{
			return;
		}

		if( defined("HOST_FILE") )
		{
			$file = HOST_FILE;
		}
		else if( defined("BASE_DIR") )
		{
			$file = BASE_DIR . "hosts.php";
		}
		else
		{
			$file = getcwd() . DIRECTORY_SEPARATOR . "hosts.php";
		}

		if( !file_exists($file) )
		{
			throw new FileNotFoundException("HostManager file not found");
		}

		$this->file = $file;

		Callback::tap(function($file) {

			$conf = require $file;

			$this->_conf['hosts']       = (array) ($conf['hosts'] ?? []);
			$this->_conf['www']         = (array) ($conf['www'] ?? []);
			$this->_conf['aliases']     = (array) ($conf['aliases'] ?? []);
			$this->_conf['ssl']         = (array) ($conf['ssl'] ?? []);
			$this->_conf['redirect']    = (array) ($conf['redirect'] ?? []);

		}, $file);
	}

	private function setRedirect( $location )
	{
		$this->redirect = true;
		$this->redirectUrl = $location;

		if( ! SERVER_CLI_MODE )
		{
			$this->redirectUrl .= $_SERVER['REQUEST_URI'] ?? '/';
		}
	}

	private function separator( $value )
	{
		$value = DIRECTORY_SEPARATOR === "/" ? $value : str_replace("/", DIRECTORY_SEPARATOR, $value);
		if( $value[0] !== DIRECTORY_SEPARATOR )
		{
			$dir = defined("APP_BASE_PATH") ? APP_BASE_PATH : getcwd() . DIRECTORY_SEPARATOR;
			$value = $dir . $value;
		}
		return $value . DIRECTORY_SEPARATOR;
	}
}