<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.08.2015
 * Time: 16:44
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Host\Host;
use RozaVerta\CmfCore\Interfaces\CreateInstanceInterface;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Support\Regexp;
use RozaVerta\CmfCore\Helper\Str;

/**
 * Class Url
 *
 * @package RozaVerta\CmfCore\Route
 */
class Url implements \Countable, CreateInstanceInterface
{
	protected $url          = "";
	protected $base         = "/";
	protected $host         = "localhost";
	protected $port         = 80;
	protected $path         = "/";
	protected $isDir        = true;
	protected $ext          = "";
	protected $lowerExt     = false;
	protected $segments     = [];
	protected $length       = 0;
	protected $dirLength    = 0;
	protected $context      = "/";
	protected $protocol     = "http";

	private $lower          = false;
	private $last           = false;
	private $mode           = 'get';
	private $basePath       = 'auto';
	private $basePrefix     = "";
	private $ready          = false;

	/**
	 * @var \RozaVerta\CmfCore\Support\Prop
	 */
	private $config;

	/**
	 * Url constructor.
	 *
	 * @param Prop|array|string $prop
	 */
	public function __construct( $prop = null )
	{
		if( ! $prop instanceof Prop )
		{
			$prop = new Prop(is_null($prop) ? [] : $prop);
		}

		if( $prop->getIs("directory") )
		{
			$prefix = trim( $prop["directory"], " \t/" );
			if( strlen($prefix) )
			{
				$this->basePrefix = "/" . $prefix . "/";
			}
		}

		if( $prop->equiv("mode", "rewrite") )
		{
			$this->mode = "rewrite";
		}

		if( $prop->equiv("lower", true) )
		{
			$this->lower = true;
		}

		if( $prop->getIs("base") )
		{
			$this->basePath = Str::lower(trim($prop->get("base")));
		}

		$this->config = $prop;
	}

	/**
	 * Create new object instance
	 *
	 * @param array ...$args
	 * @return Url
	 */
	public static function createInstance( ... $args )
	{
		$len  = count($args);
		$prop = null;
		$path = "";

		if( $len > 0 )
		{
			if( is_array($args[0]) )
			{
				$prop = $args[0];
				if( $len > 1 && is_string($args[1]) )
				{
					$path = $args[1];
				}
			}
			else if( is_string($args[0]) )
			{
				$path = $args[0];
			}
		}

		$instance = new self($prop);
		if(strlen($path))
		{
			$instance->reload($path);
		}

		return $instance;
	}

	/**
	 * Reload data from Host object
	 *
	 * @param Host $host
	 * @return $this
	 */
	public function reloadRequest( Host $host )
	{
		// host

		$pref = ($this->isSsl() ? "https://" : "http://") . $host->getHostname();
		if($host->getPort() !== 80)
		{
			$pref .= ":" . $host->getPort();
		}

		// path

		$path = '';

		if( $this->mode === 'get' )
		{
			if( ! empty($_GET['q']) )
			{
				$path .= $_GET['q'];
			}

			$path = preg_replace('|/{2,}|', '/', $path);
		}
		else if( isset($_SERVER['REQUEST_URI'], $_SERVER['SCRIPT_NAME']) )
		{
			$path = trim($_SERVER['REQUEST_URI']);
			$pos = strpos( $path, "?" );

			if( $pos !== false )
			{
				$path = substr( $path, 0, $pos );
			}

			$path = rawurldecode($path);
			if( strpos($path, $_SERVER['SCRIPT_NAME']) === 0 )
			{
				$path = (string) substr($path, strlen($_SERVER['SCRIPT_NAME']));
			}
			else if( strpos($path, dirname($_SERVER['SCRIPT_NAME'])) === 0 )
			{
				$path = (string) substr($path, strlen(dirname($_SERVER['SCRIPT_NAME'])));
			}
		}

		$path = ltrim( $path, "/" );
		if( $path !== '' )
		{
			$path = $this->cleanRelative( $path );
		}

		$this->reload( $pref . "/" . $path );

		return $this;
	}

	/**
	 * Reload data from URL path string
	 *
	 * @param string $path
	 * @return $this
	 */
	public function reload( string $path )
	{
		// clean data

		$this->context    = "/";
		$this->ext        = "";
		$this->lowerExt   = "";
		$this->segments   = [];
		$this->length     = 0;
		$this->dirLength  = 0;
		$this->last       = "";

		// set mode

		if( $this->ready )
		{
			$this->mode = "user";
		}
		else
		{
			$this->ready = true;
		}

		$host_default = defined("ORIGINAL_HOST") ? ORIGINAL_HOST : ($_SERVER['HTTP_HOST'] ?? "localhost");
		$host_protocol = defined("BASE_PROTOCOL") ? BASE_PROTOCOL : "http";

		// add host prefix ?

		if( ! preg_match('/^[a-z]{3,8}:/', $path) )
		{
			$prefix = $host_protocol;
			if( substr($path, 0, 2) === "//" )
			{
				$path = $prefix . ":" . $path;
			}
			else
			{
				$prefix .= "://";
				$prefix .= $host_default;

				if( isset($_SERVER['SERVER_PORT']) && intval($_SERVER['SERVER_PORT']) !== 80 )
				{
					$prefix .= ':' . $_SERVER['SERVER_PORT'];
				}

				if( ! strlen($path) || $path[0] !== "/" )
				{
					$path = "/" . $path;
				}

				$path = $prefix . $path;
			}

		}

		$len = strlen($this->basePrefix);
		$parse = parse_url( $path );
		if( ! $parse )
		{
			$parse = [
				"scheme" => $host_protocol,
				"host" => $host_default,
				"path" => $path
			];
		}

		$this->host = $parse["host"] ?? $host_default;
		$this->port = isset($parse["port"]) ? intval($parse["port"]) : 80;
		$this->protocol = $parse["scheme"] ?? $host_protocol;
		$this->path = $parse["path"] ?? "/";
		$this->url = $this->protocol . "://" . $this->host;
		$this->base = $this->basePath == "full" ? $this->url : "";

		if( ! strlen($this->path) || $this->path[0] !== "/" )
		{
			$this->path = "/" . $this->path;
		}

		$this->isDir = strrpos( $this->path, "/" ) === strlen( $this->path ) - 1;

		if( $len > 0 )
		{
			$this->url  .= $this->basePrefix;
			$this->base .= $this->basePrefix;

			$path = $this->path;
			if( ! $this->isDir )
			{
				$path .= "/";
			}

			if( substr($path, 0, $len) === $this->basePrefix )
			{
				$this->path = $len < strlen($this->path) ? substr($this->path, $len) : "/";
			}
		}
		else
		{
			$this->url  .= "/";
			$this->base .= "/";
		}

		if( $this->path !== "/" )
		{
			if( $this->lower )
			{
				$this->path = Str::lower($this->path);
			}

			$path = trim( $this->path, "/" );
			$this->segments = explode( "/", $path );
			$this->length  = count( $this->segments );
			$this->url .= $path;

			if( $this->isDir )
			{
				$this->url .= "/";
			}
			else if( preg_match( '|\.([a-z0-9]+)$|i', $this->path, $m ) )
			{
				$ext = "." . $m[1];
				$this->ext = $ext;
				$this->lowerExt = strtolower($ext);

				$last = $this->segments[$this->length-1];
				$this->last = substr( $last, 0, strlen($last) - strlen($ext) );
			}
			else
			{
				$this->last = $this->segments[$this->length-1];
			}
		}

		$this->dirLength = $this->length;
		$this->isDir || -- $this->dirLength;

		return $this;
	}

	/**
	 * Get URL string
	 *
	 * @return string
	 */
	public function getUrl(): string
	{
		return $this->url;
	}

	/**
	 * Get base path
	 *
	 * @return string
	 */
	public function getBase(): string
	{
		return $this->base;
	}

	/**
	 * Get URL Prefix - Protocol, Domain and Base Path
	 *
	 * @return string
	 */
	public function getPrefix(): string
	{
		$prefix  = $this->protocol . "://" . $this->host;
		$prefix .= $this->isBasePrefix() ? $this->basePrefix : "/";
		return $prefix;
	}

	/**
	 * Get domain name
	 *
	 * @return string
	 */
	public function getHost(): string
	{
		return $this->host;
	}

	/**
	 * Get port
	 *
	 * @return int
	 */
	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * Get full path
	 *
	 * @param bool $context
	 * @return string
	 */
	public function getPath( bool $context = false ): string
	{
		if($context && $this->isContext())
		{
			return $this->context . substr($this->path, 1);
		}
		else
		{
			return $this->path;
		}
	}

	/**
	 * Get offset path
	 *
	 * @param int $offset
	 * @return string
	 */
	public function getOffsetPath( int $offset ): string
	{
		if( $offset < $this->length )
		{
			$path = "";

			for( $i = $offset; $i < $this->length; $i++ )
			{
				$path .= "/" . $this->segments[$i];
			}

			if($this->isDir)
			{
				$path .= "/";
			}

			return $path;
		}
		else
		{
			return $this->isDir() ? "/" : "";
		}
	}

	/**
	 * Get the last specified segments
	 *
	 * @param int $offset
	 * @return array
	 */
	public function getOffsetSegments( int $offset ): array
	{
		return $offset < $this->length ? array_slice($this->segments, $offset) : [];
	}

	/**
	 * Get context path
	 *
	 * @return string
	 */
	public function getContext(): string
	{
		return $this->context;
	}

	/**
	 * Context used URL path, the self::shift method was previously called.
	 *
	 * @return bool
	 */
	public function isContext(): bool
	{
		return strlen($this->context) > 1;
	}

	/**
	 * URL path was closed
	 *
	 * @return bool
	 */
	public function isDir(): bool
	{
		return $this->isDir;
	}

	/**
	 * Get URL extension
	 *
	 * @return string
	 */
	public function getExt(): string
	{
		return $this->ext;
	}

	/**
	 * Get lowercase URL extension
	 *
	 * @return string
	 */
	public function getLowerExt(): string
	{
		return $this->lowerExt;
	}

	/**
	 * Get URL protocol scheme, http or https
	 *
	 * @return string
	 */
	public function getProtocol(): string
	{
		return $this->protocol;
	}

	/**
	 * Is SSL used?
	 *
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->protocol === "https";
	}

	/**
	 * Check segment existence
	 *
	 * @param int $number
	 * @return bool
	 */
	public function hasSegment( int $number ): bool
	{
		return $number >= 0 && $number < $this->length;
	}

	/**
	 * Get a specific segment
	 *
	 * @param int $number
	 * @return string
	 */
	public function getSegment( int $number = 0 ): string
	{
		if( $number < 0 )
		{
			$number = $this->length + $number;
			if( $number < 0 )
			{
				return "";
			}
		}

		return $number < $this->length ? $this->segments[$number] : "";
	}

	/**
	 * Get all segments
	 *
	 * @return array
	 */
	public function getSegments(): array
	{
		return $this->segments;
	}

	/**
	 * Count segments without last open segment
	 *
	 * @return int
	 */
	public function getDirLength(): int
	{
		return $this->dirLength;
	}

	/**
	 * Url mode, 'get' or 'rewrite'
	 *
	 * @return string
	 */
	public function getMode(): string
	{
		return $this->mode;
	}

	/**
	 * Get URL configuration
	 *
	 * @return \RozaVerta\CmfCore\Support\Prop
	 */
	public function getConfig(): Prop
	{
		return $this->config;
	}

	/**
	 * Get full url scheme string
	 *
	 * @param Context $context
	 * @param string $path
	 * @param array $query
	 *
	 * @return string
	 */
	public function makeContextUrl( Context $context, $path = '', array $query = [] ): string
	{
		if( $context->isHost() )
		{
			$url  = $context->isSsl() ? "https://" : "http://";
			$url .= $context->getHostname();
			$port = $context->getPort();
			if( $port > 0 && $port !== 80 )
			{
				$url .= ":" . $port;
			}
			$url .= $this->isBasePrefix() ? $this->basePrefix : "/";
		}
		else
		{
			$url = $this->getPrefix();
		}

		if( $context->isQuery() )
		{
			foreach( $context->getQueries() as $name => $value)
			{
				if( is_array($value) )
				{
					if( ! isset($query[$name]) || ! in_array($query[$name], $value) )
					{
						$query[$name] = current($value);
					}
				}
				else
				{
					$query[$name] = $value;
				}
			}
		}

		if( is_array($path) )
		{
			$path = implode("/", $path);
		}
		else if( strlen($path) && $path[0] === "/" )
		{
			$path = substr($path, 1);
		}

		if( $context->isPath() )
		{
			$path = trim($context->getPath(), "/") . "/" . $path;
		}

		return $this->getModeUrl($url, $path, $query);
	}

	/**
	 * Get url string
	 *
	 * @param string $path
	 * @param array $query
	 * @param bool $context
	 * @param bool|null $full
	 *
	 * @return string
	 */
	public function makeUrl( $path = '', array $query = [], bool $context = false, ? bool $full = null ): string
	{
		if( is_null($full) )
		{
			$url = $this->base;
		}
		else if( $full )
		{
			$url = $this->getPrefix();
		}
		else
		{
			$url = $this->isBasePrefix() ? $this->basePrefix : "/";
		}

		if( is_array($path) )
		{
			$path = implode('/', $path);
		}
		else if( strlen($path) && $path[0] === "/" )
		{
			$path = substr($path, 1);
		}

		if( $context && $this->isContext() )
		{
			if( strlen($path) )
			{
				$path = substr($this->context, 1) . $path;
			}
			else
			{
				$path = trim($this->context, "/") . "/";
			}
		}

		return $this->getModeUrl($url, $path, $query);
	}

	/**
	 * Create context path, shift segments
	 *
	 * @param int $delta
	 *
	 * @return $this
	 */
	public function shift( $delta = 1 )
	{
		$delta = (int) $delta;

		if( $delta > 0 && $this->dirLength )
		{
			if( $delta > $this->dirLength )
			{
				$delta = $this->dirLength;
			}

			if( $delta == 1 )
			{
				$this->context .= array_shift($this->segments) . "/";
			}
			else
			{
				$this->context .= implode("/", array_splice($this->segments, 0, $delta)) . "/";
			}

			$this->length -= $delta;
			$this->dirLength -= $delta;
			$this->path = "/";
			if($this->length > 0)
			{
				$this->path .= implode("/", $this->segments);
				if($this->isDir)
				{
					$this->path .= "/";
				}
			}
		}

		return $this;
	}

	/**
	 * Compare the URL extension with a string or array
	 *
	 * @param $ext
	 *
	 * @return bool
	 */
	public function equivExt( $ext ): bool
	{
		if( ! $this->lowerExt || ! $ext )
		{
			return false;
		}

		if( is_array($ext) )
		{
			for( $i = 0, $len = count($ext); $i < $len; $i++ )
			{
				if( $this->equivExt( (string) $ext[$i] ) )
				{
					return true;
				}
			}
			return false;
		}

		$ext = strtolower( $ext );
		if( $ext[0] !== "." )
		{
			$ext = "." . $ext;
		}

		return $this->lowerExt === $ext;
	}

	/**
	 * Compare specified URL segment
	 *
	 * @param string $test
	 * @param int $number
	 *
	 * @return bool
	 */
	public function equivSegment( string $test, int $number = 0 ): bool
	{
		if( ! isset( $this->segments[$number] ) )
		{
			return false;
		}

		if( $this->lower )
		{
			$test = Str::lower($test);
		}

		return $this->segments[$number] === $test;
	}

	/**
	 * Check if the URL segment is a number
	 *
	 * @param int $number
	 *
	 * @return bool
	 */
	public function equivNumeric( int $number = 0 ) : bool
	{
		if( ! isset( $this->segments[$number] ) )
		{
			return false;
		}
		else
		{
			$segment = $this->segments[$number];
			return is_numeric($segment) && $segment > 0 && $segment[0] !== "0";
		}
	}

	/**
	 * Check the URL scheme.
	 * The first argument used is an array; the elements of the array can be a string, a Regexp object, or a Closure.
	 *
	 * @param array $segments
	 * @param bool $isDir
	 *
	 * @return bool
	 */
	public function equivThen( array $segments, $isDir = false )
	{
		$length = count($segments);
		if( $this->length !== $length || $isDir !== $this->isDir )
		{
			return false;
		}

		for( $i = 0; $i < $length; $i++ )
		{
			$segment = $segments[$i];

			if( $segment instanceof \Closure ) $test = $segment($this->segments[$i]);
			else if( $segment instanceof Regexp ) $test = $segment->match($this->segments[$i]);
			else if( is_scalar($segment) ) $test = $this->equivSegment((string) $segment, $i);
			else $test = false;

			if( !$test )
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Get last URL segment without extension (by default)
	 *
	 * @param bool $suffix
	 *
	 * @return string
	 */
	public function getLast( bool $suffix = false ): string
	{
		if( $this->last )
		{
			return $this->last . ( $suffix ? $this->ext : "" );
		}
		else
		{
			return "";
		}
	}

	/**
	 * Matching URL segments is not case-sensitive.
	 *
	 * @return bool
	 */
	public function isLower(): bool
	{
		return $this->lower;
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 *
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return $this->length;
	}

	// protected

	/**
	 * Clean relative path
	 *
	 * @param $uri
	 *
	 * @return string
	 */
	protected function cleanRelative( $uri )
	{
		$uris = [];
		$tok = strtok($uri, '/');
		$end = strlen($uri) - 1;
		$end = $end < 0 || $uri[$end] !== "/" ? "" : "/";

		while( $tok !== false )
		{
			if(strlen($tok) > 0 && $tok !== '..' )
			{
				$uris[] = $tok;
			}
			$tok = strtok('/');
		}

		return count($uris) > 0 ? implode('/', $uris) . $end : "";
	}

	/**
	 * Is base prefix
	 *
	 * @return bool
	 */
	protected function isBasePrefix(): bool
	{
		return strlen($this->basePrefix) > 0;
	}

	/**
	 * Create a URL based on configuration mode
	 * @param $url
	 * @param $path
	 * @param array $query
	 * @return string
	 */
	protected function getModeUrl( $url, $path, array $query ): string
	{
		if( strlen($path) && $path !== "/" )
		{
			if( $this->config->getOr("mode", "get") === 'get' )
			{
				$query['q'] = $path;
			}
			else
			{
				$url .= $path;
			}
		}

		if( count($query) )
		{
			$url .= '?' . http_build_query($query);
		}

		return $url;
	}
}