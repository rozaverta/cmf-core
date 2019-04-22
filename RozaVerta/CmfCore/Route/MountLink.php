<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.04.2019
 * Time: 10:32
 */

namespace RozaVerta\CmfCore\Route;

use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;

class MountLink
{
	private $contextName = null;

	private $mountPointName;

	private $controllerName;

	private $controllerId;

	/**
	 * MountLink constructor.
	 * Mount link use format
	 * 1. context_name:mount_point_name@controller_name/controller_id
	 * 2. mount_point_name@controller_name/controller_id
	 *
	 * @param string $url
	 */
	public function __construct(string $url)
	{
		$url = trim($url);
		if($url[0] === ":")
		{
			$url = substr($url, 1);
		}

		if( !preg_match('|^([a-z0-9_\-]+:)?([a-z0-9_\-]+)@([a-z0-9_\-:]+)\/(\d+)$|', $url, $m) )
		{
			throw new InvalidArgumentException("Invalid mount link format");
		}

		if( ! empty($m[1]) )
		{
			$context = $m[1];
			$this->contextName = substr($context, 0, strlen($context) - 1);
		}

		$this->mountPointName = $m[2];
		$this->controllerName = $m[3];
		$this->controllerId = (int) $m[4];
	}

	/**
	 * @return string|null
	 */
	public function getContextName(): ?string
	{
		return $this->contextName;
	}

	/**
	 * @return string
	 */
	public function getMountPointName(): string
	{
		return $this->mountPointName;
	}

	/**
	 * @return string
	 */
	public function getControllerName(): string
	{
		return $this->controllerName;
	}

	/**
	 * @return int
	 */
	public function getControllerId(): int
	{
		return $this->controllerId;
	}
}