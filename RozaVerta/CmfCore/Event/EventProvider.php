<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.04.2016
 * Time: 18:32
 */

namespace RozaVerta\CmfCore\Event;

use ReflectionClass;
use RozaVerta\CmfCore\Database\Query\CriteriaBuilder;
use RozaVerta\CmfCore\Event\Interfaces\EventPrepareInterface;
use RozaVerta\CmfCore\Http\Events\ResponseSendEvent;
use RozaVerta\CmfCore\Module\Module;
use RozaVerta\CmfCore\Schemes\EventHandlerLinks_WithHandlers_SchemeDesigner;
use RozaVerta\CmfCore\Schemes\Events_SchemeDesigner;
use RozaVerta\CmfCore\Traits\ApplicationTrait;
use RozaVerta\CmfCore\Workshops\Event\Events\AbstractEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\DatabaseTableEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\ModuleEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\SaveConfigFileEvent;

final class EventProvider
{
	use ApplicationTrait;

	private $id = 0;
	private $name = '';
	private $completable = false;
	private $classes = [];

	public function __construct( $name, $completable = null )
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
				$this->id = $row->getId();
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

	public function load()
	{
		if( !$this->id )
		{
			return false;
		}

		$rows = $this
			->app
			->db
			->table(EventHandlerLinks_WithHandlers_SchemeDesigner::class)
			->where('event_id', $this->id)
			->get();

		/** @var EventHandlerLinks_WithHandlers_SchemeDesigner $row */
		foreach( $rows as $row )
		{
			$className = $row->getClassName();

			if( strpos($className, "\\") === false )
			{
				$className = $row->getModule()->getNamespaceName() . "Handlers\\" . $className;
			}

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

	public function getContentData()
	{
		return
			[
				"id" => $this->id,
				"name" => $this->name,
				"completable" => $this->completable,
				"classes" => $this->classes
			];
	}
}