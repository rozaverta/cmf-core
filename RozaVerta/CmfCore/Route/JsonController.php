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
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;
use RozaVerta\CmfCore\Route\Interfaces\ControllerContentOutputInterface;
use RozaVerta\CmfCore\Events\ThrowableEvent;

abstract class JsonController extends Controller implements ControllerContentOutputInterface
{
	/**
	 * JsonController constructor.
	 * @param MountPoint $mountPoint
	 * @param array $prop
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 */
	public function __construct( MountPoint $mountPoint, array $prop = [] )
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

	public function isRaw(): bool
	{
		return true;
	}

	public function output()
	{
		if( ! isset( $this->pageData["status"] ) )
		{
			$this->pageData["status"] = "ok";
		}

		if( $this->app->system('debug') && ! isset($this->pageData['debug']) )
		{
			// todo $this->page_data['debug'] = DebugStats::toArray();
		}

		$this
			->app
			->response
			->json($this->pageData);
	}

	public function getContentType(): string
	{
		return "application/json";
	}
}