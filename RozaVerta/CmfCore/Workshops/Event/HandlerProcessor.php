<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 14:04
 */

namespace RozaVerta\CmfCore\Workshops\Event;

use ReflectionClass;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Query\Criteria;
use RozaVerta\CmfCore\Event\EventHelper;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Event\Interfaces\EventPrepareInterface;
use RozaVerta\CmfCore\Module\WorkshopModuleProcessor;
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
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function createHandler( string $className ): void
	{
		$className = $this->getClassName($className);

		if( ! class_exists($className, true) )
		{
			throw new HandlerClassNotFoundException( "Handler class \"{$className}\" not found." );
		}

		$ref = new ReflectionClass($className);
		$className = $this->getOriginalClassName($className);

		if( ! $ref->implementsInterface( EventPrepareInterface::class ) )
		{
			throw new HandlerImplementsException( "Handler class \"{$className}\" must be implements of \"" . EventPrepareInterface::class . "\" interface." );
		}

		if( $this->getHandlerId($className) !== null )
		{
			$this->addAlert( Text::text( 'The "%s" handler was already exists.', $className ) );
			return;
		}

		// dispatch event
		$event = new Events\HandlerCreateEvent($this, $className);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "The creation of an \"{$className}\" handler is aborted." );
		}

		$handlerId = (int) $this
			->db
			->builder( EventHandlers_SchemeDesigner::getTableName() )
			->insertGetId( [
				"module_id" => $this->getModuleId(),
				"class_name" => $className
			]);

		$dispatcher->complete($handlerId);
		$this->addDebug( Text::text( 'The "%s" handler is successfully created.', $className ) );
	}

	/**
	 * Delete handler
	 *
	 * @param string $className
	 *
	 * @throws \Doctrine\DBAL\DBALException
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
			throw new EventAbortException( "The \"{$className}\" handler delete aborted." );
		}

		$this->db->transactional(function(Connection $conn) use ($handlerId) {

			// remove class from handler table
			$conn
				->builder( EventHandlers_SchemeDesigner::getTableName() )
				->whereId($handlerId)
				->delete();

			// remove links
			$conn
				->builder( EventHandlerLinks_SchemeDesigner::getTableName() )
				->where("handler_id", $handlerId)
				->delete();
		});

		$dispatcher->complete($handlerId);
		$this->addDebug( Text::text( 'The "%s" handler and handler links is successfully removed.', $className ) );
	}

	/**
	 * Handler has been added to the database.
	 *
	 * @param string $className
	 * @param null   $handlerId
	 *
	 * @return bool
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function registered( string $className, & $handlerId = null ): bool
	{
		$id = $this->getHandlerId( $this->getOriginalClassName( $className ) );
		if( is_null( $id ) )
		{
			return false;
		}

		$handlerId = $id;
		return true;
	}

	/**
	 * Change priority
	 *
	 * @param int $linkId
	 * @param int $priority
	 *
	 * @throws EventAccessException
	 * @throws HandlerLinkNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function priority( int $linkId, int $priority ): void
	{
		$row = $this
			->db
			->builder( EventHandlerLinks_SchemeDesigner::getTableName(), "ecl" )
			->leftJoin( EventHandlers_SchemeDesigner::getTableName(), "ec", static function( Criteria $criteria ) {
				$criteria->columns( "ecl.handler_id", "el.id" );
			} )
			->leftJoin( Events_SchemeDesigner::getTableName(), "e", static function( Criteria $criteria ) {
				$criteria->columns( "ecl.event_id", "e.id" );
			} )
			->whereId($linkId, 'ecl.id')
			->select([
				'ecl.priority', 'ec.class_name', 'ec.module_id', 'e.name'
			])
			->first();

		if( !$row )
		{
			throw new HandlerLinkNotFoundException( "Handler link \"{$linkId}\" not found." );
		}

		$moduleId = (int) $row["module_id"];
		$className = $row["class_name"];
		$eventName = $row["name"];

		if( $moduleId !== $this->getModuleId() )
		{
			throw new EventAccessException( "It is permissible to change the \"{$eventName}\" event data only for the \"{$moduleId}\" module." );
		}

		$oldPriority = (int) $row["priority"];

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
			throw new EventAbortException( "The establishment of a connection between the \"{$eventName}\" event and the \"{$className}\" handler was aborted." );
		}

		$this
			->db
			->builder( EventHandlerLinks_SchemeDesigner::getTableName() )
			->whereId($linkId)
			->update([ "priority" => $priority ]);

		$dispatcher->complete($linkId);
		$this->addDebug( Text::text( 'The connection between the "%s" event and the "%s" handler has been updated.', $eventName, $className ) );
	}

	/**
	 * Create link
	 *
	 * @param string   $className
	 * @param string   $eventName
	 *
	 * @param int|null $priority
	 * @param bool     $replace
	 *
	 * @throws EventNotFoundException
	 * @throws HandlerNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function link( string $className, string $eventName, ? int $priority = null, bool $replace = false ): void
	{
		$className = $this->getOriginalClassName($className);
		$handlerId = $this->getHandlerId($className);
		if( ! $handlerId )
		{
			throw new HandlerNotFoundException( "Class name \"{$className}\" not found." );
		}

		if( ! EventHelper::exists($eventName, $eventId) )
		{
			throw new EventNotFoundException( "Event \"{$eventName}\" not found." );
		}

		$linkId = $this->getLinkId($handlerId, $eventId);
		if( $linkId )
		{
			$replace || $this->addError( "The connection between the \"{$eventName}\" event and the \"{$className}\" handler was already exists." );
			return;
		}

		if( is_null($priority) )
		{
			$priority = $this
				->db
					->builder( EventHandlerLinks_SchemeDesigner::getTableName() )
				->where("event_id", $eventId)
				->max("priority") + 1;
		}

		// dispatch event
		$event = new Events\LinkEvent($this, $className, $eventName, $priority);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "The establishment of a connection between the \"{$eventName}\" event and the \"{$className}\" handler was aborted." );
		}

		$linkId = (int) $this
			->db
			->builder( EventHandlerLinks_SchemeDesigner::getTableName() )
			->insertGetId( [
				"handler_id" => $handlerId,
				"event_id" => $eventId,
				"priority" => $priority
			]);

		$dispatcher->complete($linkId);
		$this->addDebug( Text::text( 'Event "%s" and handler of "%s" connected.', $eventName, $className ) );
	}

	/**
	 * Unlink handler
	 *
	 * @param string $className
	 * @param string $eventName
	 *
	 * @throws EventNotFoundException
	 * @throws HandlerNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function unlink( string $className, string $eventName ): void
	{
		$className = $this->getOriginalClassName($className);
		$handlerId = $this->getHandlerId($className);
		if( ! $handlerId )
		{
			throw new HandlerNotFoundException( "Class name \"{$className}\" not found." );
		}

		if( ! EventHelper::exists($eventName, $eventId) )
		{
			throw new EventNotFoundException( "Event \"{$eventName}\" not found." );
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
			throw new EventAbortException( "Disconnection between the \"{$eventName}\" event and the \"{$className}\" handler was aborted." );
		}

		$this
			->db
			->builder( EventHandlerLinks_SchemeDesigner::getTableName() )
			->whereId( $linkId )
			->delete();

		$dispatcher->complete($linkId);
		$this->addDebug( Text::text( 'The "%s" event and the "%s" handler are no longer associated.', $eventName, $className ) );
	}

	/**
	 * Get handler database SchemeDesigner
	 *
	 * @param string $className
	 *
	 * @return EventHandlers_SchemeDesigner|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function getHandlerScheme( string $className ): ?EventHandlers_SchemeDesigner
	{
		/** @var EventHandlers_SchemeDesigner|false $handler */
		$handler = EventHandlers_SchemeDesigner::find()
			->where("class_name", $this->getOriginalClassName($className))
			->where("module_id", $this->getModuleId())
			->first();

		return $handler ? $handler : null;
	}

	/**
	 * Get link database SchemeDesigner
	 *
	 * @param string $className
	 * @param string $eventName
	 *
	 * @return EventHandlerLinks_SchemeDesigner|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function getLinkScheme( string $className, string $eventName ): ?EventHandlerLinks_SchemeDesigner
	{
		$handlerId = $this->getHandlerId($this->getOriginalClassName($className));
		if( ! $handlerId || ! EventHelper::exists($eventName, $eventId) )
		{
			return null;
		}

		/** @var EventHandlerLinks_SchemeDesigner|false $link */
		$link = EventHandlerLinks_SchemeDesigner::find()
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
			return $this->getModule()->getNamespaceName() . "Handlers\\" . $className;
		}
		else
		{
			return $className;
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

	/**
	 * Get link ID
	 *
	 * @param int $handlerId
	 * @param int $eventId
	 *
	 * @return int|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	protected function getLinkId(int $handlerId, int $eventId): ? int
	{
		$linkId = $this
			->db
			->builder( EventHandlerLinks_SchemeDesigner::getTableName() )
			->where("handler_id", $handlerId)
			->where("event_id", $eventId)
			->value("id");

		return $linkId && is_numeric($linkId) ? (int) $linkId : null;
	}

	/**
	 * Get handler ID
	 *
	 * @param string $className
	 *
	 * @return int|null
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	protected function getHandlerId( string $className ): ? int
	{
		$handlerId = $this
			->db
			->builder( EventHandlers_SchemeDesigner::getTableName() )
			->where("class_name", $className)
			->where("module_id", $this->getModuleId())
			->value("id");

		return $handlerId && is_numeric($handlerId) ? (int) $handlerId : null;
	}
}