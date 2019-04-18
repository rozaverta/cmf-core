<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.09.2017
 * Time: 2:22
 */

namespace RozaVerta\CmfCore\Controllers;

use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Route\Controller;
use RozaVerta\CmfCore\Route\Interfaces\ControllerContentOutputInterface;
use RozaVerta\CmfCore\Route\MountPoint;
use RozaVerta\CmfCore\Support\Prop;

final class Redirect extends Controller implements ControllerContentOutputInterface
{
	/** @noinspection PhpMissingParentConstructorInspection
	 *
	 * Redirect constructor.
	 *
	 * @param ModuleInterface $module
	 * @param MountPoint $mountPoint
	 * @param array $data
	 */
	public function __construct( ModuleInterface $module, MountPoint $mountPoint, array $data = [] )
	{
		$this->module = $module;
		$this->mountPoint = $mountPoint;
		$this->items = $data;
		$this->properties = new Prop($data);
		$this->appInit();
	}

	public function ready(): bool
	{
		if($this->properties->getIs("location"))
		{
			$this->setId($this->mountPoint->getId());
			return true;
		}
		else
		{
			return false;
		}
	}

	public function complete()
	{
		$this->app->response->redirect(
			$this->properties->getOr("location", "/"),
			(bool) $this->properties->getOr("permanent", false),
			(bool) $this->properties->getOr("refresh", false)
		);
	}

	/**
	 * Render content is raw output.
	 *
	 * @return boolean
	 */
	public function isRaw(): bool
	{
		return true;
	}

	/**
	 * Render content.
	 *
	 * @return void
	 */
	public function output()
	{
		$response = $this->app->response;
		if( ! $response->isSent() )
		{
			$response->send();
		}
	}

	public function getContentType(): string
	{
		return "http/response";
	}
}