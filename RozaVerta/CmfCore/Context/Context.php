<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.08.2018
 * Time: 16:27
 */

namespace RozaVerta\CmfCore\Context;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Module\Module;
use RozaVerta\CmfCore\Schemes\Context_SchemeDesigner;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;

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
	private $moduleIds;

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
	 * @param int[] $moduleIds
	 */
	public function __construct( Context_SchemeDesigner $contextSchemeDesigner, array $moduleIds = [] )
	{
		$this->schemeDesigner = $contextSchemeDesigner;
		$this->moduleIds = $moduleIds;

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
	 * Raw result object
	 *
	 * @return Context_SchemeDesigner
	 */
	public function getSchemeDesignerInstance(): Context_SchemeDesigner
	{
		return $this->schemeDesigner;
	}

	/**
	 * @return bool
	 */
	public function isHost(): bool
	{
		return $this->host;
	}

	/**
	 * @return bool
	 */
	public function isQuery(): bool
	{
		return $this->query;
	}

	/**
	 * @return bool
	 */
	public function isPath(): bool
	{
		return strlen($this->path) > 0;
	}

	/**
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->ssl;
	}

	/**
	 * @return int[]
	 */
	public function getModuleIds(): array
	{
		return $this->moduleIds;
	}

	/**
	 * @param Module $module
	 * @return bool
	 */
	public function hasModule( Module $module ): bool
	{
		return $this->hasModuleId( $module->getId() );
	}

	/**
	 * @param int $id
	 * @return bool
	 */
	public function hasModuleId( int $id ): bool
	{
		return in_array($id, $this->moduleIds, true);
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
		$all["module_ids"] = $this->getModuleIds();
		return $all;
	}

	public function getArrayForVarExport(): array
	{
		return [
			"schemeDesigner" => $this->schemeDesigner,
			"moduleIds" => $this->moduleIds
		];
	}

	static public function __set_state( $data )
	{
		return new Context($data["schemeDesigner"], $data["moduleIds"]);
	}
}