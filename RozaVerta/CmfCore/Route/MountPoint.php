<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 20.03.2019
 * Time: 10:21
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Module\Module;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Route\Interfaces\MountPointInterface;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;
use RozaVerta\CmfCore\Traits\GetTrait;

class MountPoint implements MountPointInterface, VarExportInterface
{
	use ModuleGetterTrait {
		getModuleId as private getModuleNativeId;
	}
	use GetIdentifierTrait;
	use GetTrait;

	private $moduleId = 0;

	protected $homePage = false;

	protected $is404 = false;

	protected $basePath = false;

	protected $pathName = "mount_point";

	protected $items;

	protected $length = -1;

	protected $path = "/";

	protected $close = false;

	protected $segments = [];

	public function __construct( int $id, int $moduleId, string $path, array $properties = [] )
	{
		$path = trim($path);

		if($path[0] === "@")
		{
			$path = strtolower(substr($path, 1));
			if($path === "homepage") $this->homePage = true;
			else if($path === "404") $this->is404 = true;
			else
			{
				if(strlen($path)) $this->pathName = $path;
				$this->basePath = true;
				$this->close = true;
			}
		}
		else
		{
			$last = strlen($path);
			if(!$last || $path[0] !== "/")
			{
				$path = "/" . $path;
			}
			else
			{
				$last --;
			}

			if($last === 0)
			{
				$this->basePath = true;
			}

			$this->path = $path;
			if($path[$last] === "/")
			{
				$this->close = true;
			}

			$path = trim($path, "/");
			if(strlen($path))
			{
				$this->segments = explode("/", $path);
			}
		}

		$this->moduleId = $moduleId;
		$this->setId($id);
		$this->items = $properties;
	}

	public function getModuleId(): int
	{
		return $this->hasModule() ? $this->getModuleNativeId() : $this->moduleId;
	}

	/**
	 * Load module
	 *
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceNotFoundException
	 * @throws \RozaVerta\CmfCore\Module\Exceptions\ResourceReadException
	 */
	protected function reloadModule()
	{
		$this->setModule( Module::module($this->moduleId) );
	}

	/**
	 * @return string
	 */
	public function getPathName(): string
	{
		return $this->pathName;
	}

	/**
	 * @return bool
	 */
	public function isHomePage(): bool
	{
		return $this->homePage;
	}

	/**
	 * @return bool
	 */
	public function is404(): bool
	{
		return $this->is404;
	}

	/**
	 * @return bool
	 */
	public function isBasePath(): bool
	{
		return $this->basePath;
	}

	/**
	 * @return string
	 */

	public function getPath(): string
	{
		return $this->path;
	}

	/**
	 * @return bool
	 */
	public function isContainer(): bool
	{
		return $this->close;
	}

	/**
	 * Count elements of an object
	 * @link https://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return count($this->segments);
	}

	/**
	 * @param int $number
	 * @return string|null
	 */
	public function getSegment( int $number = 0 ): ? string
	{
		return $this->segments[$number] ?? null;
	}

	/**
	 * @return array
	 */
	public function getSegments(): array
	{
		return $this->segments;
	}

	public function getArrayForVarExport(): array
	{
		$path = $this->path;
		if($this->homePage) $path = "@homePage";
		else if($this->is404) $path = "@404";
		else if($this->pathName !== "mount_point") $path = "@" . $this->pathName;

		return [
			"id" => $this->getId(),
			"moduleId" => $this->getModuleId(),
			"path" => $path,
			"properties" => $this->items
		];
	}

	static public function __set_state( $data )
	{
		return new MountPoint((int) $data["id"], (int) $data["moduleId"], (string) $data["path"], $data["properties"] ?? []);
	}
}