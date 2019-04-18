<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 20.03.2019
 * Time: 18:38
 */

namespace RozaVerta\CmfCore\View;

use RozaVerta\CmfCore\Cache\Cache;
use RozaVerta\CmfCore\Route\Interfaces\ControllerInterface;
use RozaVerta\CmfCore\Traits\ApplicationTrait;

class PageCache
{
	use ApplicationTrait;

	protected $ready = false;

	/**
	 * @var Cache
	 */
	protected $cache;

	protected $pagePackage = "main";
	protected $pageTemplate = "main";
	protected $pageCode = 200;
	protected $pageHeaders = [];
	protected $pageProtected = [];
	protected $pageContentType = "text/html";
	protected $pageBody = "";
	protected $pageData = [];

	/**
	 * @var PluginCacheNode[]
	 */
	protected $pagePlugins = [];

	public function __construct( ControllerInterface $controller )
	{
		$this->appInit();

		$name = "page-" . $controller->getId();
		$prop = $controller->getProperties();
		if( empty($prop['prefix']) )
		{
			$prefix = str_replace( ':', '_', str_replace( '::', '/', $controller->getName() ) );
		}
		else
		{
			$prefix = $prop["prefix"];
		}

		unset( $prop['prefix'] );

		$this->cache = $this
			->app
			->cache
			->newCache(
				$name,
				$prefix,
				$prop,
				$this
					->app
					->cache
					->hasStore("page") ? "page" : null
			);

		if( $this->cache->ready() )
		{
			$data = $this->cache->import();
			if( isset($data["package"], $data["template"], $data["body"], $data["data"], $data["headers"], $data["protected"], $data["contentType"], $data["plugins"]) )
			{
				$this->ready = true;

				$this->pagePackage = $data["package"];
				$this->pageTemplate = $data["template"];
				$this->pageCode = $data["code"];
				$this->pageHeaders = $data["headers"];
				$this->pageProtected = $data["protected"];
				$this->pageContentType = $data["contentType"];
				$this->pageBody = $data["body"];
				$this->pageData = $data["data"];
				$this->pagePlugins = $data["plugins"];
			}
			else
			{
				$this->app->log->line("Invalid data import for page cache by the " . $controller->getName() . " controller, page " . $controller->getId());
			}
		}
	}

	public function ready(): bool
	{
		return $this->ready;
	}

	public function forget(): bool
	{
		if( $this->cache->forget() )
		{
			$this->pagePackage = "main";
			$this->pageTemplate = "main";
			$this->pageCode = 200;
			$this->pageHeaders = [];
			$this->pageProtected = [];
			$this->pageContentType = "text/html";
			$this->pageBody = "";
			$this->pageData = [];
			$this->pagePlugins = [];

			return true;
		}
		else
		{
			return false;
		}
	}

	public function setTemplateName( string $name )
	{
		$this->pageTemplate = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getTemplateName(): string
	{
		return $this->pageTemplate;
	}

	public function setContentType( string $name )
	{
		$this->pageContentType = $name;
		return $this;
	}

	public function setPackageName( string $name )
	{
		$this->pagePackage = $name;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getPackageName(): string
	{
		return $this->pagePackage;
	}

	/**
	 * @return string
	 */
	public function getContentType(): string
	{
		return $this->pageContentType;
	}

	public function setCode( int $code )
	{
		$this->pageCode = $code;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getStatusCode(): int
	{
		return $this->pageCode;
	}

	public function setHeaders( array $headers )
	{
		$this->pageHeaders = $headers;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getHeaders(): array
	{
		return $this->pageHeaders;
	}

	public function setPageData( array $data )
	{
		$this->pageData = $data;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getPageData(): array
	{
		return $this->pageData;
	}

	public function setBody( string $text )
	{
		$this->pageBody = $text;
		return $this;
	}

	public function getBody(): string
	{
		return $this->pageBody;
	}

	public function setProtectedKeys( array $keys )
	{
		$this->pageProtected = $keys;
		return $this;
	}

	public function getProtectedKeys(): array
	{
		return $this->pageProtected;
	}

	public function setPlugins( array $plugins )
	{
		$this->pagePlugins = $plugins;
		return $this;
	}

	/**
	 * @return PluginCacheNode[]
	 */
	public function getPlugins(): array
	{
		return $this->pagePlugins;
	}

	public function save()
	{
		$data = [
			"template"      => $this->pageTemplate,
			"code"          => $this->pageCode,
			"headers"       => $this->pageHeaders,
			"protected"     => $this->pageProtected,
			"contentType"   => $this->pageContentType,
			"plugins"       => $this->pagePlugins
		];

		$export = $this->cache->export($data);

		if( !$export )
		{
			$this->app->log->line("Cannot write page cache");
		}

		return $export;
	}
}