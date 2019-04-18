<?php

namespace PHPSTORM_META {

	override(\RozaVerta\CmfCore\App::load(0),
		map([
			"log" => \RozaVerta\CmfCore\Log\LogManager::class,
			"phpExport" => \RozaVerta\CmfCore\Helper\PhpExport::class,
			"url" => \RozaVerta\CmfCore\Route\Url::class,
			"view" => \RozaVerta\CmfCore\View\View::class,
			"filesystem" => \RozaVerta\CmfCore\Filesystem\Filesystem::class,
			"lang" => \RozaVerta\CmfCore\Language\LanguageManager::class,
			"session" => \RozaVerta\CmfCore\Session\SessionManager::class,
			"database" => \RozaVerta\CmfCore\Database\DatabaseManager::class,
			"db" => \RozaVerta\CmfCore\Database\Connection::class,
			"controller" => \RozaVerta\CmfCore\Route\Interfaces\ControllerInterface::class,
			"context" => \RozaVerta\CmfCore\Context\Context::class,
			"response" => \RozaVerta\CmfCore\Http\Response::class,
			"request" => \RozaVerta\CmfCore\Http\Request::class,
			"host" => \RozaVerta\CmfCore\Host\HostManager::class,
			"event" => \RozaVerta\CmfCore\Event\EventManager::class,
			"cache" => \RozaVerta\CmfCore\Cache\CacheManager::class,
		])
	);

	expectedReturnValues(\RozaVerta\CmfCore\Log\LogManager::getInstance(), \RozaVerta\CmfCore\Log\LogManager::class);
}