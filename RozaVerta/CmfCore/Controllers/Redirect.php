<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.09.2017
 * Time: 2:22
 */

namespace RozaVerta\CmfCore\Controllers;

use RozaVerta\CmfCore\Route\Controller;
use RozaVerta\CmfCore\Route\Interfaces\ControllerContentOutputInterface;
use RozaVerta\CmfCore\Route\Interfaces\MountPointInterface;
use RozaVerta\CmfCore\Support\Prop;

/**
 * Class Redirect
 *
 * @package RozaVerta\CmfCore\Controllers
 */
final class Redirect extends Controller implements ControllerContentOutputInterface
{
	/** @noinspection PhpMissingParentConstructorInspection
	 *
	 * Redirect constructor.
	 *
	 * @param MountPointInterface $mountPoint
	 * @param array $data
	 */
	public function __construct( MountPointInterface $mountPoint, array $data = [] )
	{
		$this->setModule($mountPoint->getModule());
		$this->mountPoint = $mountPoint;
		$this->items = $data;
		$this->properties = new Prop($data);
	}

	/**
	 * Ready (initial) page data
	 *
	 * @return bool
	 */
	public function initialize(): bool
	{
		if( $this->properties->has( "location" ) )
		{
			$this->setId($this->mountPoint->getId());
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Complete. Load all data for page
	 *
	 * @return void
	 *
	 * @throws \Throwable
	 */
	public function complete(): void
	{
		self::service( "response" )->redirect(
			$this->properties->get( "location", "/" ),
			(bool) $this->properties->get( "permanent", false ),
			(bool) $this->properties->get( "refresh", false )
		);

		parent::complete();
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
	 *
	 * @throws \Throwable
	 */
	public function output()
	{
		$response = self::service( "response" );
		if( ! $response->isSent() )
		{
			$response->send();
		}
	}

	/**
	 * Get content type
	 *
	 * @return string
	 */
	public function getContentType(): string
	{
		return "http/response";
	}
}