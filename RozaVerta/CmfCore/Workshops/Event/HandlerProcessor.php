<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 14:04
 */

namespace RozaVerta\CmfCore\Workshops\Event;

use ReflectionClass;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Event\EventHelper;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Event\Interfaces\EventPrepareInterface;
use RozaVerta\CmfCore\Schemes\EventHandlerLinks_SchemeDesigner;
use RozaVerta\CmfCore\Schemes\EventHandlers_SchemeDesigner;
use RozaVerta\CmfCore\Schemes\Events_SchemeDesigner;
use RozaVerta\CmfCore\Support\Text;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\EventAccessException;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\HandlerClassNotFoundException;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\HandlerImplementsException;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\HandlerLinkNotFoundException;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\HandlerNotFoundException;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\EventNotFoundException;

/**
 * Class HandlerProcessor
 *
 * @package RozaVerta\CmfCore\Workshops\Event
 */
class HandlerProcessor extends Workshop
{
	/**
	 * Register new handler
	 *
	 * @param string $className
	 *
	 * @throws HandlerClassNotFoundException
	 * @throws \ReflectionException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 */
	public function createHandler( string $className ): void
	{
		$className = $this->getClassName($className);

		if( ! class_exists($className, true) )
		{
			throw new HandlerClassNotFoundException("Handler class '{$className}' not found");
		}

		$ref = new ReflectionClass($className);
		$className = $this->getOriginalClassName($className);

		if( ! $ref->implementsInterface( EventPrepareInterface::class ) )
		{
			throw new HandlerImplementsException("Handler class '{$className}' must be implements of '" . EventPrepareInterface::class . "' interface");
		}

		if( $this->getHandlerId($className) !== null )
		{
			$this->addAlert(Text::text("The %s handler was already exists", $className));
			return;
		}

		// dispatch event
		$event = new Events\HandlerCreateEvent($this, $className);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException("The creation of an '{$className}' handler is aborted");
		}

		$this
			->db
			->table(EventHandlers_SchemeDesigner::getTableName())
			->insert([
				"module_id" => $this->getModuleId(),
				"class_name" => $className
			]);

		$handlerId = $this->db->lastInsertId();
		$dispatcher->complete($handlerId);
		$this->addDebug(Text::text("The %s handler is successfully created", $className));
	}

	/**
	 * Delete handler
	 *
	 * @param string $className
	 *
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 * @throws \Throwable
	 */
	public function deleteHandler( string $className ): void
	{
		$className = $this->getOriginalClassName($className);
		$handlerId = $this->getHandlerId($className);

		if( $handlerId === null )
		{
			return;
		}

		// dispatch event
		$event = new Events\HandlerDeleteEvent($this, $className);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException("The '{$className}' handler delete aborted");
		}

		$this->db->transactional(function(Connection $conn) use ($handlerId) {

			// remove class from handler table
			$conn
				->table(EventHandlers_SchemeDesigner::getTableName())
				->whereId($handlerId)
				->delete();

			// remove links
			$conn
				->table(EventHandlerLinks_SchemeDesigner::getTableName())
				->where("handler_id", $handlerId)
				->delete();
		});

		$dispatcher->complete($handlerId);
		$this->addDebug(Text::text("The %s handler and handler links is successfully removed", $className));
	}

	/**
	 * Change priority
	 *
	 * @param int $linkId
	 * @param int $priority
	 *
	 * @throws EventAccessException
	 * @throws HandlerLinkNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 */
	public function priority( int $linkId, int $priority ): void
	{
		$row = $this
			->db
			->table(EventHandlerLinks_SchemeDesigner::getTableName(), "ecl")
			->leftJoin("ecl", EventHandlers_SchemeDesigner::getTableName(), "ec", "ecl.handler_id = el.id")
			->leftJoin("ecl", Events_SchemeDesigner::getTableName(), "e", "ecl.event_id = e.id")
			->whereId($linkId, 'ecl.id')
			->select([
				'ecl.priority', 'ec.class_name', 'ec.module_id', 'e.name'
			])
			->first();

		if( !$row )
		{
			throw new HandlerLinkNotFoundException("Handler link '{$linkId}' not found");
		}

		$moduleId  = (int) $row->get("module_id");
		$className = $row->get("class_name");
		$eventName = $row->get("name");

		if( $moduleId !== $this->getModuleId() )
		{
			throw new EventAccessException("It is permissible to change the '{$eventName}' event data only for the '{$moduleId}' module");
		}

		$oldPriority = (int) $row->get("priority");

		if($oldPriority === $priority)
		{
			return;
		}

		// dispatch event
		$event = new Events\LinkEvent($this, $className, $eventName, $priority);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException("The establishment of a connection between the {$eventName} event and the {$className} handler was aborted");
		}

		$this
			->db
			->table(EventHandlerLinks_SchemeDesigner::getTableName())
			->whereId($linkId)
			->update([ "priority" => $priority ]);

		$dispatcher->complete($linkId);
		$this->addDebug(Text::text("The connection between the %s event and the %s handler has been updated", $eventName, $className));
	}

	/**
	 * Create link
	 *
	 * @param string $className
	 * @param string $eventName
	 *
	 * @param int|null $priority
	 *
	 * @throws HandlerNotFoundException
	 * @throws EventNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 */
	public function link( string $className, string $eventName, ? int $priority = null ): void
	{
		$className = $this->getOriginalClassName($className);
		$handlerId = $this->getHandlerId($className);
		if( ! $handlerId )
		{
			throw new HandlerNotFoundException("Class name '{$className}' not found");
		}

		if( ! EventHelper::exists($eventName, $eventId) )
		{
			throw new EventNotFoundException("Event '{$eventName}' not found");
		}

		$linkId = $this->getLinkId($handlerId, $eventId);
		if( $linkId )
		{
			$this->addError("The connection between the {$eventName} event and the {$className} handler was already exists");
			return;
		}

		if( is_null($priority) )
		{
			$priority = $this
				->db
				->table(EventHandlerLinks_SchemeDesigner::getTableName())
				->where("event_id", $eventId)
				->max("priority") + 1;
		}

		// dispatch event
		$event = new Events\LinkEvent($this, $className, $eventName, $priority);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException("The establishment of a connection between the {$eventName} event and the {$className} handler was aborted");
		}

		$this
			->db
			->table(EventHandlerLinks_SchemeDesigner::getTableName())
			->insert([
				"handler_id" => $handlerId,
				"event_id" => $eventId,
				"priority" => $priority
			]);

		$linkId = $this->db->lastInsertId();
		$dispatcher->complete($linkId);
		$this->addDebug(Text::text("Event %s and handler of %s connected", $eventName, $className));
	}

	/**
	 * @param string $className
	 * @param string $eventName
	 *
	 * @throws HandlerNotFoundException
	 * @throws EventNotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 * @throws \RozaVerta\CmfCore\Exceptions\WriteException
	 */
	public function unlink( string $className, string $eventName ): void
	{
		$className = $this->getOriginalClassName($className);
		$handlerId = $this->getHandlerId($className);
		if( ! $handlerId )
		{
			throw new HandlerNotFoundException("Class name '{$className}' not found");
		}

		if( ! EventHelper::exists($eventName, $eventId) )
		{
			throw new EventNotFoundException("Event '{$eventName}' not found");
		}

		$linkId = $this->getLinkId($handlerId, $eventId);
		if( !$linkId )
		{
			return;
		}

		// dispatch event
		$event = new Events\UnlinkEvent($this, $linkId, $className, $eventName);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException("Disconnection between the {$eventName} event and the {$className} handler was aborted");
		}

		$this
			->db
			->table(EventHandlerLinks_SchemeDesigner::getTableName())
			->whereId($eventId)
			->delete();

		$linkId = $this->db->lastInsertId();
		$dispatcher->complete($linkId);
		$this->addDebug(Text::text("The %s event and the %s handler are no longer associated.", $eventName, $className));
	}

	public function getHandlerScheme( string $className ): ?EventHandlers_SchemeDesigner
	{
		/** @var EventHandlers_SchemeDesigner|false $handler */
		$handler = $this
			->db
			->table(EventHandlers_SchemeDesigner::class)
			->where("class_name", $this->getOriginalClassName($className))
			->where("module_id", $this->getModuleId())
			->first();

		return $handler ? $handler : null;
	}

	public function getLinkScheme( string $className, string $eventName ): ?EventHandlerLinks_SchemeDesigner
	{
		$handlerId = $this->getHandlerId($this->getOriginalClassName($className));
		if( ! $handlerId || ! EventHelper::exists($eventName, $eventId) )
		{
			return null;
		}

		/** @var EventHandlerLinks_SchemeDesigner|false $link */
		$link = $this
			->db
			->table(EventHandlerLinks_SchemeDesigner::class)
			->where("handler_id", $handlerId)
			->where("event_id", $eventId)
			->first();

		return $link ? $link : null;
	}

	/**
	 * Get full handler class name
	 *
	 * @param string $className
	 * @return string
	 */
	public function getClassName( string $className ): string
	{
		$className = ltrim($className, "\\");
		if( strpos($className, "\\") === false )
		{
			return $className;
		}
		else
		{
			return  $this->getModule()->getNamespaceName() . "Handlers\\" . $className;
		}
	}

	/**
	 * Get class name in database
	 *
	 * @param string $className
	 * @return string
	 */
	public function getOriginalClassName( string $className ): string
	{
		$className = ltrim($className, "\\");
		if( strpos($className, "\\") === false )
		{
			return $className;
		}

		$pref = $this->getModule()->getNamespaceName() . "Handlers\\";

		if( strpos($className, $pref) === 0 )
		{
			$suffix = substr($className, strlen($pref));
			if( strpos($suffix, "\\") === false )
			{
				return $suffix;
			}
		}

		return $className;
	}

	// protected

	protected function getLinkId(int $handlerId, int $eventId): ? int
	{
		$linkId = $this
			->db
			->table(EventHandlerLinks_SchemeDesigner::getTableName())
			->where("handler_id", $handlerId)
			->where("event_id", $eventId)
			->value("id");

		return $linkId && is_numeric($linkId) ? (int) $linkId : null;
	}

	protected function getHandlerId( string $className ): ? int
	{
		$handlerId = $this
			->db
			->table(EventHandlers_SchemeDesigner::getTableName())
			->where("class_name", $className)
			->where("module_id", $this->getModuleId())
			->value("id");

		return $handlerId && is_numeric($handlerId) ? (int) $handlerId : null;
	}
}