<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 2:21
 */

namespace RozaVerta\CmfCore\Module;

use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Module\Interfaces\ModularInterface;

/**
 * Class Modular
 *
 * @package RozaVerta\CmfCore\Module
 */
abstract class Modular implements Arrayable, ModularInterface
{
	/**
	 * @var string
	 */
	protected $name;

	/**
	 * @var string
	 */
	protected $key;

	/**
	 * @var bool
	 */
	protected $route;

	/**
	 * @var bool
	 */
	protected $front;

	/**
	 * @var string
	 */
	protected $title;

	/**
	 * @var string
	 */
	protected $version;

	/**
	 * @var string
	 */
	protected $pathname;

	/**
	 * @var string
	 */
	protected $namespaceName;

	/**
	 * @var array
	 */
	protected $support = [];

	/**
	 * @var array
	 */
	protected $extra = [];

	/**
	 * @return bool
	 */
	public function isFront(): bool
	{
		return $this->front;
	}

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Get module key name
	 *
	 * @return string
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	/**
	 * Get module title
	 *
	 * @return string
	 */
	public function getTitle(): string
	{
		return $this->title;
	}

	/**
	 * Module use router
	 *
	 * @return bool
	 */
	public function isRoute(): bool
	{
		return $this->route;
	}

	/**
	 * Get module version
	 *
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * Get module path
	 *
	 * @return string
	 */
	public function getPathname(): string
	{
		return $this->pathname;
	}

	/**
	 * Get module namespace
	 *
	 * @return string
	 */
	public function getNamespaceName(): string
	{
		return $this->namespaceName;
	}

	/**
	 * Get all support addons
	 *
	 * @return array
	 */
	public function getSupport(): array
	{
		return $this->support;
	}

	/**
	 * @return array
	 */
	public function getExtras(): array
	{
		return $this->extra;
	}

	/**
	 * Addons module is supported
	 *
	 * @param string $name
	 * @param null|string $version
	 * @return bool
	 */
	public function support( string $name, ?string $version = null ): bool
	{
		if( ! array_key_exists($name, $this->support) )
		{
			return false;
		}
		if( ! $version )
		{
			return true;
		}

		$support = $this->support[$name];
		return $support === "*" || version_compare( $support, $version ) > -1;
	}

	/**
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			'name' => $this->name,
			'key' => $this->key,
			'route' => $this->route,
			'title' => $this->title,
			'front' => $this->front,
			'version' => $this->version,
			'pathname' => $this->pathname, // dynamic for config
			'namespaceName' => $this->namespaceName, // dynamic for config
			'support' => $this->support,
			'extra' => $this->extra
		];
	}
}