<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.09.2015
 * Time: 0:15
 */

namespace RozaVerta\CmfCore\Route;

use Doctrine\DBAL\DBALException;
use RozaVerta\CmfCore\Interfaces\ThrowableInterface;
use RozaVerta\CmfCore\Route\Interfaces\ControllerContentOutputInterface;
use RozaVerta\CmfCore\Events\ThrowableEvent;
use RozaVerta\CmfCore\Route\Interfaces\MountPointInterface;

/**
 * Class JsonController
 *
 * @package RozaVerta\CmfCore\Route
 */
abstract class JsonController extends Controller implements ControllerContentOutputInterface
{
	/**
	 * JsonController constructor.
	 *
	 * @param MountPointInterface $mountPoint
	 * @param array $prop
	 *
	 * @throws \Throwable
	 */
	public function __construct( MountPointInterface $mountPoint, array $prop = [] )
	{
		parent::__construct($mountPoint, $prop);

		$this
			->app
			->response
			->header("Content-Type", "application/json; charset=utf-8");

		$this
			->app
			->event
			->dispatcher(ThrowableEvent::eventName())
			->register(
				function( ThrowableEvent $event )
				{
					$throwable = $event->throwable;
					$code = $throwable->getCode();

					if( ! $event->app->loaded('controller') || $event->app->controller !== $this || in_array( $code, [403, 404, 500] ) )
					{
						return null;
					}

					// hide sql query
					$this->pageData =
						[
							"status" => "error",
							"errorMessage" => $event->throwable instanceof DBALException ? 'DataBase fatal error' : $event->throwable->getMessage()
						];

					if( $code )
					{
						$this->pageData['errorCode'] = $code;
					}

					if($throwable instanceof ThrowableInterface)
					{
						$this->pageData['errorName'] = $throwable->getCodeName();
					}

					return function()
					{
						defined("SERVER_CLI_MODE") && SERVER_CLI_MODE || $this->output();
					};
				}, "controller.jsonThrowable");
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
		if( ! isset( $this->pageData["status"] ) )
		{
			$this->pageData["status"] = "ok";
		}

		$this
			->app
			->response
			->json($this->pageData);
	}

	/**
	 * Get content type
	 *
	 * @return string
	 */
	public function getContentType(): string
	{
		return "application/json";
	}
}