<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 22:25
 */

namespace RozaVerta\CmfCore\Support;

use RozaVerta\CmfCore\Interfaces\WorkshopInterface;
use RozaVerta\CmfCore\Log\Traits\LoggableTrait;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
use RozaVerta\CmfCore\Module\Traits\ModuleGetterTrait;
use RozaVerta\CmfCore\Traits\ServiceTrait;

/**
 * Class Workshop
 *
 * @package RozaVerta\CmfCore\Support
 */
abstract class Workshop implements WorkshopInterface
{
	use ServiceTrait
	{
		thisServices as private thisServicesTrait;
	}
	use LoggableTrait;
	use ModuleGetterTrait;

	/** @var \RozaVerta\CmfCore\App $app */
	protected $app;

	/** @var \RozaVerta\CmfCore\Log\LogManager $log */
	protected $log;

	/** @var \RozaVerta\CmfCore\Filesystem\Filesystem $filesystem */
	protected $filesystem;

	/** @var \RozaVerta\CmfCore\Language\LanguageManager $lang */
	protected $lang;

	/** @var \RozaVerta\CmfCore\Session\SessionManager $session */
	protected $session;

	/** @var \RozaVerta\CmfCore\Database\DatabaseManager $database */
	protected $database;

	/** @var \RozaVerta\CmfCore\Database\Connection $db */
	protected $db;

	/** @var \RozaVerta\CmfCore\Host\HostManager $host */
	protected $host;

	/** @var \RozaVerta\CmfCore\Event\EventManager $event */
	protected $event;

	/** @var \RozaVerta\CmfCore\Cache\CacheManager $cache */
	protected $cache;

	/**
	 * Workshop constructor.
	 *
	 * @param WorkshopModuleProcessor $module
	 *
	 * @throws \Throwable
	 */
	public function __construct( WorkshopModuleProcessor $module )
	{
		$this->setModule($module);
		$this->thisServices();
	}

	protected function thisServices( ...$args )
	{
		$services = [ "app", "log", "filesystem", "lang", "session", "database", "db", "host", "event", "cache" ];
		if( count( $args ) )
		{
			foreach( $args as $service )
			{
				if( !in_array( $service, $services, true ) )
				{
					$services[] = $service;
				}
			}
		}
		$this->thisServicesTrait( ...$services );
	}
}