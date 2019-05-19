<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.04.2016
 * Time: 18:32
 */

namespace RozaVerta\CmfCore\Event;

use ReflectionClass;
use RozaVerta\CmfCore\Event\Interfaces\EventPrepareInterface;
use RozaVerta\CmfCore\Http\Events\ResponseSendEvent;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Schemes\EventHandlerLinks_WithHandlers_SchemeDesigner;
use RozaVerta\CmfCore\Schemes\Events_SchemeDesigner;
use RozaVerta\CmfCore\Traits\ApplicationTrait;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;
use RozaVerta\CmfCore\Workshops\Event\Events\AbstractEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\DatabaseTableEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\ModuleEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\SaveConfigFileEvent;

/**
 * Class EventProvider
 *
 * @package RozaVerta\CmfCore\Event
 */
final class EventProvider implements Arrayable
{
	use ApplicationTrait;
	use GetIdentifierTrait;

	private $name = '';

	private $completable = false;

	private $classes = [];

	/**
	 * EventProvider constructor.
	 *
	 * @param string $name
	 * @param bool|null $completable
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \ReflectionException
	 * @throws \Throwable
	 */
	public function __construct( string $name, ? bool $completable = null )
	{
		$this->appInit();

		$this->name = trim($name);
		if( is_bool($completable) )
		{
			$this->completable = $completable;
		}

		// ready database only for install system

		if( $this->app->isInstall() )
		{
			$row = $this
				->app
				->db
				->table(Events_SchemeDesigner::class)
				->where('name', $this->name)
				->first();

			/** @var Events_SchemeDesigner $row */
			if( $row )
			{
				$this->setId($row->getId());
				$this->completable = $row->isCompletable();
			}
		}
		else
		{
			$this->completable = in_array($name, [
				ResponseSendEvent::eventName(),
				ModuleEvent::eventName(),
				SaveConfigFileEvent::eventName(),
				DatabaseTableEvent::eventName(),
				AbstractEvent::eventName(),
			], true);
		}
	}

	/**
	 * Load event handlers
	 *
	 * @return int handlers count
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function load(): int
	{
		$id = $this->getId();
		if( !$id )
		{
			return 0;
		}

		$rows = $this
			->app
			->db
			->table(EventHandlerLinks_WithHandlers_SchemeDesigner::class)
			->where('event_id', $id)
			->get();

		/** @var EventHandlerLinks_WithHandlers_SchemeDesigner $row */
		foreach( $rows as $row )
		{
			$className = $row->getClassName();
			try {
				$ref = new ReflectionClass($className);
			}
			catch(\ReflectionException $e) {
				$this->app->log->line($e->getMessage());
				continue;
			}

			if( ! $ref->implementsInterface( EventPrepareInterface::class ) )
			{
				$this->app->log->line("Class '{$className}' should implement the interface " . EventPrepareInterface::class);
			}
			else
			{
				$this->classes[] = $className;
			}
		}

		return count($this->classes);
	}

	/**
	 * Event name
	 *
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * Event is completable
	 *
	 * @return bool
	 */
	public function isCompletable(): bool
	{
		return $this->completable;
	}

	/**
	 * Get event handlers, classes list
	 *
	 * @return string[]
	 */
	public function getHandlers(): array
	{
		return $this->classes;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return
			[
				"id" => $this->getId(),
				"name" => $this->name,
				"completable" => $this->completable,
				"classes" => $this->classes
			];
	}
}