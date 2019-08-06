<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.04.2018
 * Time: 3:40
 */

namespace RozaVerta\CmfCore\Workshops\Event;

use RozaVerta\CmfCore\Event\Dispatcher;
use RozaVerta\CmfCore\Event\EventHelper;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Helper\Str;
use RozaVerta\CmfCore\Schemes\Events_SchemeDesigner;
use RozaVerta\CmfCore\Support\Text;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\EventAccessException;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\EventAlreadyExistsException;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\EventInvalidNameException;
use RozaVerta\CmfCore\Workshops\Event\Exceptions\EventNotFoundException;

/**
 * Class EventProcessor
 *
 * @package RozaVerta\CmfCore\Workshops\Event
 */
class EventProcessor extends Workshop
{
	/**
	 * Create (register) new event
	 *
	 * @param string $eventName
	 * @param string $eventTitle
	 * @param bool $isCompletable
	 *
	 * @return void
	 *
	 * @throws EventAlreadyExistsException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function create( string $eventName, string $eventTitle = "", bool $isCompletable = false ): void
	{
		if( EventHelper::exists($eventName) )
		{
			throw new EventAlreadyExistsException( "The \"{$eventName}\" event already exists." );
		}

		if( ! EventHelper::validModuleName($eventName, $this->getModule()) )
		{
			throw new EventInvalidNameException( "Invalid event name \"{$eventName}\" for the \"" . $this->getModule()->getName() . "\" module." );
		}

		$eventTitle = $this->formatEventTitle($eventTitle, $eventName);

		// dispatch event
		$event = new Events\CreateEvent($this, $eventName, $eventTitle, $isCompletable);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "The creation of an \"{$eventName}\" event is aborted." );
		}

		$this
			->db
			->builder( Events_SchemeDesigner::getTableName() )
			->insert([
				"name" => $eventName,
				"title" => $eventTitle,
				"completable" => $isCompletable,
				"module_id" => $this->getModuleId(),
			]);

		$this->complete($dispatcher, $eventName, "create");
	}

	/**
	 * Update event data
	 *
	 * @param string $eventName
	 * @param string $title
	 * @param bool $completable
	 *
	 * @return void
	 *
	 * @throws EventAccessException
	 * @throws EventNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function update( string $eventName, string $title = "", bool $completable = false ): void
	{
		$row = EventHelper::getSchemeDesigner($eventName);
		if( !$row )
		{
			throw new EventNotFoundException( "The \"{$eventName}\" event not found." );
		}

		$this->updateProcess($row, $title, $completable);
	}

	/**
	 * Create (register) or update event
	 *
	 * @param string $eventName
	 * @param string $eventTitle
	 * @param bool $isCompletable
	 *
	 * @return void
	 *
	 * @throws EventAccessException
	 * @throws EventAlreadyExistsException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function replace( string $eventName, string $eventTitle = "", bool $isCompletable = false ): void
	{
		$row = EventHelper::getSchemeDesigner($eventName);
		if( $row )
		{
			$this->updateProcess($row, $eventTitle, $isCompletable);
		}
		else
		{
			$this->create($eventName, $eventTitle, $isCompletable);
		}
	}

	/**
	 * Rename event
	 *
	 * @param string $eventName
	 * @param string $newEventName
	 * @return void
	 *
	 * @throws EventAccessException
	 * @throws EventAlreadyExistsException
	 * @throws EventNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function rename( string $eventName, string $newEventName ): void
	{
		// check old
		$row = EventHelper::exists($eventName, $eventId, $moduleId);
		if( !$row )
		{
			throw new EventNotFoundException( "The \"{$eventName}\" event not found." );
		}

		$this->permissible($moduleId, $eventName);

		if( $eventName === $newEventName )
		{
			return;
		}

		// check new name exists
		if( EventHelper::exists($newEventName) )
		{
			throw new EventAlreadyExistsException( "The \"{$newEventName}\" event already exists." );
		}

		if( ! EventHelper::validModuleName($newEventName, $this->getModule()) )
		{
			throw new EventInvalidNameException( "Invalid event name \"{$newEventName}\" for the \"" . $this->getModule()->getName() . "\" module." );
		}

		// dispatch event
		$event = new Events\RenameEvent($this, $eventName, $newEventName);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "The rename of an \"{$eventName}\" event is aborted." );
		}

		$this
			->db
			->builder( Events_SchemeDesigner::getTableName() )
			->whereId($eventId)
			->update(["name" => $newEventName]);

		$dispatcher->complete("rename", $eventName, $newEventName);
		$this->addDebug( Text::text( "The \"%s\" event is successfully renamed. New event name is \"%s\".", $eventName, $newEventName ) );
	}

	/**
	 * Delete event
	 *
	 * @param string $eventName
	 * @return void
	 *
	 * @throws EventAccessException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function delete( string $eventName ): void
	{
		// check old
		if( ! EventHelper::exists($eventName, $eventId, $moduleId) )
		{
			return;
		}

		$this->permissible($moduleId, $eventName);

		// dispatch event
		$event = new Events\DeleteEvent($this, $eventName);
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "The \"{$eventName}\" event delete aborted." );
		}

		$this
			->db
			->builder( Events_SchemeDesigner::getTableName() )
			->whereId($eventId)
			->delete();

		$this->complete($dispatcher, $eventName, "delete");
	}

	/**
	 * Event has been added to the database.
	 *
	 * @param string $eventName
	 * @param null   $eventId
	 *
	 * @return bool
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \RozaVerta\CmfCore\Exceptions\NotFoundException
	 */
	public function registered( string $eventName, & $eventId = null ): bool
	{
		if( !EventHelper::exists( $eventName, $id, $moduleId ) || $moduleId !== $this->getModuleId() )
		{
			return false;
		}

		$eventId = $id;
		return true;
	}

	// protected

	/**
	 * Update Database Records
	 *
	 * @param Events_SchemeDesigner $row
	 * @param string $eventTitle
	 * @param bool $isCompletable
	 *
	 * @return void
	 *
	 * @throws EventAccessException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	protected function updateProcess( Events_SchemeDesigner $row, string $eventTitle, bool $isCompletable ): void
	{
		$eventName = $row->getName();

		$this->permissible($row->getModuleId(), $eventName);

		$eventTitle = $this->formatEventTitle($eventTitle, $eventName);
		$update = [];

		if( $eventTitle !== $row->getTitle() )
		{
			$update["title"] = $eventTitle;
		}

		if( $isCompletable !== $row->isCompletable() )
		{
			$update["completable"] = $isCompletable;
		}

		if( ! count($update) )
		{
			return;
		}

		// dispatch event
		$event = new Events\UpdateEvent($this, $eventName, $eventTitle, $isCompletable, array_keys($update));
		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException( "The \"{$eventName}\" event update aborted." );
		}

		$this
			->db
			->builder( Events_SchemeDesigner::getTableName() )
			->whereId($row->getId())
			->update($update);

		$this->complete($dispatcher, $eventName, "update");
	}

	/**
	 * Complete action, write log
	 *
	 * @param Dispatcher $dispatcher
	 * @param string $eventName
	 * @param string $action
	 */
	protected function complete(Dispatcher $dispatcher, string $eventName, string $action): void
	{
		$dispatcher->complete($action, $eventName);
		$this->addDebug( Text::text( "The \"%s\" event is successfully " . rtrim( $action, "e" ) . "ed", $eventName ) );
	}

	/**
	 * Format event name
	 *
	 * @param string $eventTitle
	 * @param string $eventName
	 *
	 * @return string
	 */
	protected function formatEventTitle(string $eventTitle, string $eventName): string
	{
		$eventTitle = trim($eventTitle);

		if( strlen($eventTitle) < 1 )
		{
			$eventTitle = $eventName . " event";
		}

		if( Str::len($eventTitle) > 255 )
		{
			$eventTitle = Str::cut($eventTitle, 0, 255);
		}

		return $eventTitle;
	}

	/**
	 * Check the permission of the event module
	 *
	 * @param int $moduleId
	 * @param string $eventName
	 *
	 * @throws EventAccessException
	 */
	protected function permissible(int $moduleId, string $eventName)
	{
		if( $this->getModuleId() !== $moduleId )
		{
			throw new EventAccessException( "It is permissible to change the \"{$eventName}\" event data only for the \"{$moduleId}\" module." );
		}
	}
}