<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.09.2017
 * Time: 1:52
 */

namespace RozaVerta\CmfCore\Event;

use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Cache\Cache;
use RozaVerta\CmfCore\Event\Interfaces\EventInterface;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Exceptions\WriteException;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;

/**
 * Class EventManager
 *
 * @package RozaVerta\CmfCore\Event
 */
final class EventManager
{
	use SingletonInstanceTrait;

	/**
	 * @var \RozaVerta\CmfCore\Event\Dispatcher[]
	 */
	private $dispatchers = [];

	/**
	 * Get event manager element from cache
	 *
	 * @param string $name
	 *
	 * @return Dispatcher
	 *
	 * @throws NotFoundException
	 * @throws WriteException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function dispatcher(string $name): Dispatcher
	{
		if( isset($this->dispatchers[$name]))
		{
			return $this->dispatchers[ $name ];
		}

		// system has been installed
		// use default mode
		if(App::getInstance()->isInstall())
		{
			$cache = new Cache($name, 'events');

			if( $cache->ready() )
			{
				$data = $cache->import();
			}
			else
			{
				$event = new EventProvider($name);
				if( $event->load() === false )
				{
					throw new NotFoundException("Event '{$name}' is not registered in system");
				}

				$data = $event->toArray();
				if(! $cache->export($data))
				{
					throw new WriteException("Can't write cache data for the '{$name}' event");
				}
			}
		}
		else
		{
			$data = (new EventProvider($name))->toArray();
		}

		$manager = new Dispatcher(
			$data["name"],
			$data["completable"],
			function (Dispatcher $manager) use ($data) {
				foreach($data["classes"] as $class_name)
				{
					/** @var \RozaVerta\CmfCore\Event\Interfaces\EventPrepareInterface $class */
					$class = new $class_name();
					$class->prepare($manager);
				}
			});

		$this->dispatchers[$name] = $manager;

		return $manager;
	}

	/**
	 * @param string $name
	 * @param \Closure $callback
	 * @param int $priority
	 *
	 * @return Dispatcher
	 *
	 * @throws NotFoundException
	 * @throws WriteException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function listen(string $name, \Closure $callback, $priority = 0)
	{
		return $this->dispatcher($name)->listen($callback, $priority);
	}

	/**
	 * @param EventInterface $event
	 * @param \Closure|null $callback
	 *
	 * @return \RozaVerta\CmfCore\Support\Collection
	 *
	 * @throws NotFoundException
	 * @throws WriteException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function dispatch(EventInterface $event, \Closure $callback = null)
	{
		return $this->dispatcher($event->getName())->dispatch($event, $callback);
	}

	/**
	 * @param string $name
	 *
	 * @return bool
	 *
	 * @throws NotFoundException
	 * @throws WriteException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function isRun(string $name): bool
	{
		return $this->dispatcher($name)->isRun();
	}

	/**
	 * @param string $name
	 *
	 * @return int
	 *
	 * @throws NotFoundException
	 * @throws WriteException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function count(string $name): int
	{
		return $this->dispatcher($name)->count();
	}
}