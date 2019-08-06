<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 16:27
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Route\Interfaces\MountPointInterface;
use RozaVerta\CmfCore\Schemes\Context_SchemeDesigner;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;

/**
 * Class Context
 *
 * @package RozaVerta\CmfCore\Route
 */
class Context implements Arrayable, VarExportInterface
{
	use GetIdentifierTrait;

	/**
	 * @var \RozaVerta\CmfCore\Schemes\Context_SchemeDesigner
	 */
	private $schemeDesigner;

	/**
	 * @var int[]
	 */
	private $routerIds;

	private $host = false;

	private $hostname = "";

	private $query = false;

	private $queries = [];

	private $name = "";

	private $path = "";

	private $ssl = false;

	private $title = "";

	private $description = "";

	private $port = 80;

	private $defaultContext = false;

	private $properties = [];

	/**
	 * Context constructor.
	 *
	 * @param Context_SchemeDesigner $contextSchemeDesigner
	 * @param int[] $routerIds
	 */
	public function __construct( Context_SchemeDesigner $contextSchemeDesigner, array $routerIds = [] )
	{
		$this->schemeDesigner = $contextSchemeDesigner;
		$this->routerIds = $routerIds;

		$this->id = $contextSchemeDesigner->getId();
		$this->name = $contextSchemeDesigner->getName();
		$this->host = $contextSchemeDesigner->isHost();
		$this->hostname = $contextSchemeDesigner->getHostname();
		$this->query = $contextSchemeDesigner->isQueryPath();
		$this->queries = $contextSchemeDesigner->getQueries();
		$this->path = $contextSchemeDesigner->getPath();
		$this->ssl = $contextSchemeDesigner->isHostSsl();
		$this->title = $contextSchemeDesigner->getTitle();
		$this->description = $contextSchemeDesigner->getDescription();
		$this->port = $contextSchemeDesigner->getHostPort();
		$this->defaultContext = $contextSchemeDesigner->isDefaultContext();
		$this->properties = $contextSchemeDesigner->getProperties();
	}

	/**
	 * Raw result object.
	 *
	 * @return Context_SchemeDesigner
	 */
	public function getSchemeDesignerInstance(): Context_SchemeDesigner
	{
		return $this->schemeDesigner;
	}

	/**
	 * The context uses the host name.
	 *
	 * @return bool
	 */
	public function isHost(): bool
	{
		return $this->host;
	}

	/**
	 * The context uses query parameters.
	 *
	 * @return bool
	 */
	public function isQuery(): bool
	{
		return $this->query;
	}

	/**
	 * The context uses the path prefix.
	 *
	 * @return bool
	 */
	public function isPath(): bool
	{
		return strlen($this->path) > 0;
	}

	/**
	 * The context uses the ssl protocol.
	 *
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->ssl;
	}

	/**
	 * Get router IDs.
	 *
	 * @return int[]
	 */
	public function getRouterIds(): array
	{
		return $this->routerIds;
	}

	/**
	 * @param MountPointInterface $point
	 * @return bool
	 */
	public function hasMountPoint( MountPointInterface $point ): bool
	{
		return $this->hasMountPointId( $point->getId() );
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function hasMountPointId( int $id ): bool
	{
		return in_array($id, $this->routerIds, true);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * @return string
	 */
	public function getHostname(): string
	{
		return $this->hostname;
	}

	/**
	 * @return int
	 */
	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * @return string
	 */
	public function getProtocol(): string
	{
		return $this->ssl ? "https" : "http";
	}

	/**
	 * @return string
	 */
	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @return array
	 */
	public function getQueries(): array
	{
		return $this->queries;
	}

	/**
	 * @return bool
	 */
	public function isDefault(): bool
	{
		return $this->defaultContext;
	}

	/**
	 * @return array
	 */
	public function getProperties(): array
	{
		return $this->properties;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		$all = $this->schemeDesigner->toArray();
		$all["router_ids"] = $this->getRouterIds();
		return $all;
	}

	public function getArrayForVarExport(): array
	{
		return [
			"schemeDesigner" => $this->schemeDesigner,
			"routerIds" => $this->routerIds
		];
	}

	static public function __set_state( $data )
	{
		return new Context($data["schemeDesigner"], $data["routerIds"]);
	}
}