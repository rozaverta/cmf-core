<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2016
 * Time: 19:34
 */

namespace RozaVerta\CmfCore\View;

use RozaVerta\CmfCore\Database\DatabaseManager as DB;
use RozaVerta\CmfCore\Cache\Cache;
use RozaVerta\CmfCore\Filesystem\Iterator;
use RozaVerta\CmfCore\Helper\Callback;
use RozaVerta\CmfCore\Helper\Json;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Schemes\TemplatePackages_SchemeDesigner;
use RozaVerta\CmfCore\View\Exceptions\TemplateNotFoundException;

final class Package implements Interfaces\PackageInterface, VarExportInterface
{
	use GetIdentifierTrait;
	use ModuleGetterTrait;

	private $items = [];

	private $chunks = [];

	static private $store = [];

	private function __construct( array $items )
	{
		$this->items = $items;
	}

	private function __clone()
	{
	}

	/**
	 * @param string $templateName
	 * @return Template
	 * @throws TemplateNotFoundException
	 */
	public function getTemplate(string $templateName): Template
	{
		if( !in_array($templateName, $this->items["templates"]) )
		{
			throw new Exceptions\TemplateNotFoundException("The '{$templateName}' template not found in the '" . $this->getName() . "' package");
		}

		return new Template($this, $templateName, $this->items["templatesProperties"][$templateName] ?? []);
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->items["name"];
	}

	/**
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->items["title"];
	}

	/**
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->items["version"];
	}

	/**
	 * @return string
	 */
	public function getLicense(): string
	{
		return $this->items["license"];
	}

	/**
	 * @return string
	 */
	public function getReadme(): string
	{
		return $this->items["readme"];
	}

	/**
	 * Package description
	 *
	 * @return string
	 */
	public function getDescription(): string
	{
		return $this->items["description"];
	}

	/**
	 * Package author
	 *
	 * @return string
	 */
	public function getAuthor(): string
	{
		return $this->items["author"];
	}

	/**
	 * Package url link
	 *
	 * @return string
	 */
	public function getLink(): string
	{
		return $this->items["link"];
	}

	/**
	 * Get web assets prefix path
	 *
	 * @return string
	 */
	public function getAssets(): string
	{
		return $this->items["assets"];
	}

	/**
	 * Get view assets path.
	 *
	 * @return string
	 */
	public function getAssetsPath(): string
	{
		return $this->items["assetsPath"];
	}

	/**
	 * Get view package path.
	 *
	 * @return string
	 */
	public function getViewPath(): string
	{
		return $this->items["viewPath"];
	}

	/**
	 * Include function file.
	 *
	 * @param View $view
	 * @return $this
	 */
	public function loadFunctions(View $view)
	{
		$this->items["functional"] && Callback::tap( function($file) use($view) { require $file; }, $this->items["functional"] );
		return $this;
	}

	/**
	 * @param $name
	 * @return null|string
	 */
	public function getChunkFilename( string $name ): ? string
	{
		return $this->items["chunks"][$name] ?? null;
	}

	/**
	 * Check chunk exists
	 *
	 * @param string $name
	 * @return bool
	 */
	public function chunkExists( string $name ): bool
	{
		return isset($this->items["chunks"][$name]);
	}

	/**
	 * @param int $id
	 * @return Package
	 * @throws Exceptions\PackageNotFoundException
	 */
	static public function package( int $id ): Package
	{
		if( ! isset(self::$store[$id]) )
		{
			$cache = new Cache( $id, 'template/package' );
			if( $cache->ready() )
			{
				self::$store[$id] = $cache->import();
			}
			else
			{
				self::$store[$id] = self::load($id);
				$cache->export(self::$store[$id]);
			}
		}

		return self::$store[$id];
	}

	static public function getIdFromName( string $name ): ? int
	{
		static $idn = null;

		if( is_null($idn) )
		{
			// load packages IDs
			$cache = new Cache('id_from_name', 'template/package');
			if( $cache->ready() )
			{
				$idn = $cache->import();
			}
			else
			{
				$all = DB
					::table(TemplatePackages_SchemeDesigner::class)
					->orderBy("name")
					->get();

				/** @var TemplatePackages_SchemeDesigner $item */
				foreach($all as $item)
				{
					$idn[$item->getName()] = $item->getId();
				}

				$cache->export($idn);
			}
		}

		return $idn[$name] ?? null;
	}

	/**
	 * @param int $id
	 * @return Package
	 * @throws Exceptions\PackageNotFoundException
	 */
	static private function load(int $id): Package
	{
		$row = DB
			::table(TemplatePackages_SchemeDesigner::class)
			->whereId($id)
			->first();

		/** @var TemplatePackages_SchemeDesigner $row */
		if( ! $row )
		{
			throw new Exceptions\PackageNotFoundException("The '{$id}' template package not found");
		}

		$name = $row->getName();
		$manifest = Path::view($name . "/manifest.json");
		if( !file_exists($manifest))
		{
			throw new Exceptions\PackageNotFoundException("The '{$name}' manifest package file not found");
		}

		$data = Json::getArrayProperties(file_get_contents($manifest));
		if( ! isset($data["templates"]) )
		{
			throw new Exceptions\PackageNotFoundException("Error reading '{$name}' manifest package file or invalid content schema");
		}

		$templates = [];
		foreach((array) $data["templates"] as $template => $props)
		{
			if(is_int($template))
			{
				$templates[(string) $props] = [];
			}
			else
			{
				$templates[$template] = is_array($props) ? $props : [];
			}
		}

		$data["id"] = $id;
		$data["moduleId"] = $row->getModuleId();
		$data["name"] = $name;
		$data["templates"] = [];
		$data["templatesProperties"] = [];
		$data["chunks"] = [];
		$data["title"] = $data["title"] ?? ("Package " . $name);
		$data["version"] = $data["version"] ?? "1.0.0";
		$data["license"] = $data["license"] ?? "";
		$data["readme"] = $data["readme"] ?? "";
		$data["description"] = $data["description"] ?? "";
		$data["author"] = $data["author"] ?? "";
		$data["link"] = $data["link"] ?? "";
		$data["assets"] = Path::assetsWeb($name . "/");
		$data["assetsPath"] = Path::assets($name . "/");
		$data["viewPath"] = Path::view($name . "/");
		$data["functional"] = false;

		$pref = Path::view($name . "/" );

		/** @var \SplFileInfo $file */
		foreach( (new Iterator($pref))->getFiles() as $file)
		{
			if($file->getExtension() !== "php")
			{
				continue;
			}

			$name = $file->getFilename();
			$path = $file->getPathname();

			if($name === "functional.inc.php")
			{
				$data["functional"] = $path;
				continue;
			}

			$name = $path;
			if(strpos($name, $pref) === 0)
			{
				$name = substr($name, strlen($pref));

			}
			else
			{
				continue;
			}

			$name = substr($name, 0, strlen($name) - 4);
			$name = str_replace(DIRECTORY_SEPARATOR, ".", $name);
			$data["chunks"][$name] = $path;

			if( array_key_exists($name, $templates) )
			{
				$data["templates"][] = $name;
				$data["templatesProperties"][$name] = $templates[$name];
			}
		}

		return new Package($data);
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return $this->items;
	}

	public function getArrayForVarExport(): array
	{
		return $this->toArray();
	}

	static public function __set_state( $data )
	{
		$id = $data["id"];
		return isset(self::$store[$id]) ? self::$store[$id] : new Package( $data );
	}
}