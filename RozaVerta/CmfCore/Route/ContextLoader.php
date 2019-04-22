<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 20.03.2019
 * Time: 14:16
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Route\Context;
use RozaVerta\CmfCore\Schemes\ContextRouterLinks_SchemeDesigner;
use RozaVerta\CmfCore\Cache\Cache;
use RozaVerta\CmfCore\Database\DatabaseManager as DB;
use RozaVerta\CmfCore\Host\Interfaces\HostInterface;
use RozaVerta\CmfCore\Schemes\Context_SchemeDesigner;
use RozaVerta\CmfCore\Support\Collection;

class ContextLoader
{
	/**
	 * @var HostInterface
	 */
	private $host;

	/**
	 * @var string
	 */
	private $urlPath;

	/**
	 * @var array
	 */
	private $getParams = [];

	/**
	 * @var Collection|null
	 */
	private $collection = null;

	/**
	 * @var Context|null
	 */
	private $context = null;

	/**
	 * @var bool
	 */
	private $loaded = false;

	public function __construct( HostInterface $host, string $urlPath = "", ? array $getParams = null )
	{
		$this->host = $host;
		$this->urlPath = $urlPath;
		$this->getParams = is_null($getParams) ? $_GET : $getParams;
	}

	public function getContext(): ?Context
	{
		return $this->context;
	}

	public function getCollection(): ?Collection
	{
		return $this->collection;
	}

	/**
	 * @return $this
	 */
	public function reset()
	{
		if( !$this->loaded )
		{
			$this->context = null;
			$this->collection = null;
			$this->loaded = false;
		}

		return $this;
	}

	/**
	 * @param Cache|null $cache
	 * @return $this
	 */
	public function load(? Cache $cache = null)
	{
		if($this->loaded)
		{
			return $this;
		}

		/** @var Context $context */
		/** @var Context[] $ctx */
		/** @var array $item */
		/** @var Context_SchemeDesigner $scheme */

		if( $cache && $cache->ready() )
		{
			$ctx = $cache->import();
		}
		else
		{
			$ctx = [];
			$routers = [];

			/** @var ContextRouterLinks_SchemeDesigner[] $links */
			$links = DB
				::table(ContextRouterLinks_SchemeDesigner::class)
				->get();

			foreach($links as $link)
			{
				$contextId = $link->getContextId();
				$routerId = $link->getRouterId();

				if( !isset($routers[$contextId]) )
				{
					$routers[$contextId] = [$routerId];
				}
				else if( !in_array($routerId, $routers[$contextId], true) )
				{
					$routers[$contextId][] = $routerId;
				}
			}

			/** @var Context_SchemeDesigner[] $schemes */
			$schemes = DB::table(Context_SchemeDesigner::class)->get();

			foreach($schemes as $scheme)
			{
				$ctx[$scheme->getName()] = new Context( $scheme, $routers[$scheme->getId()] ?? [] );
			}

			if( $cache && count($ctx) )
			{
				$cache->export($ctx);
			}
		}

		$this->collection = new Collection();

		$isPriorityHost  = false;
		$isPriorityPath  = false;
		$isPriorityQuery = false;
		$path            = $this->urlPath;

		foreach($ctx as $name => $context)
		{
			$isHost    = $context->isHost();
			$isPath    = $context->isPath();
			$isQuery   = $context->isQuery();
			$isOnce    = $isHost || $isPath || $isQuery;

			if($isOnce)
			{
				$this->collection[] = $context;
			}

			if(
				$isHost  && ! $this->isHost( $context ) ||
				$isPath  && ! ( $path && strpos($path, $context->getPath() . "/") === 0 ) ||
				$isQuery && ! $this->isQuery( $context->getQueries() ) ||
				$isPriorityHost  && ! $isHost ||
				$isPriorityPath  && ! ($isPath || $isHost) ||
				$isPriorityQuery && ! $isOnce ||
				! $isOnce && ! $context->isDefault()
			)
			{
				continue;
			}

			$this->context   = $context;
			$isPriorityHost  = $isHost;
			$isPriorityPath  = $isPath;
			$isPriorityQuery = $isQuery;
		}

		return $this;
	}

	private function isQuery(array $queries)
	{
		foreach($queries as $name => $value)
		{
			if( !isset($this->getParams[$name]) )
			{
				return false;
			}
			if( is_array($value) )
			{
				if( ! in_array($this->getParams[$name], $value) )
				{
					return false;
				}
			}
			else if( strlen($value) && $this->getParams[$name] !== $value )
			{
				return false;
			}
		}

		return true;
	}

	private function isHost(Context $context)
	{
		if( $context->getHostname() !== $this->host->getHostname() ||
			$context->isSsl() !== $this->host->isSsl()
		)
		{
			return false;
		}

		$port = $context->getPort();
		if( $port > 0 && $this->host->getPort() !== $port )
		{
			return false;
		}

		return true;
	}
}