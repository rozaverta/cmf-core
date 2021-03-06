<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.04.2016
 * Time: 18:32
 */

namespace RozaVerta\CmfCore\Event;

use Doctrine\DBAL\Types\Type;
use ReflectionClass;
use RozaVerta\CmfCore\Event\Interfaces\EventPrepareInterface;
use RozaVerta\CmfCore\Http\Events\ResponseSendEvent;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Schemes\EventHandlerLinks_WithHandlers_SchemeDesigner;
use RozaVerta\CmfCore\Schemes\Events_SchemeDesigner;
use RozaVerta\CmfCore\Traits\GetIdentifierTrait;
use RozaVerta\CmfCore\Traits\ServiceTrait;
use RozaVerta\CmfCore\Workshops\Event\Events\AbstractEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\DatabaseTableEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\ModuleEvent;
use RozaVerta\CmfCore\Workshops\Module\Events\SaveConfigFileEvent;
use RozaVerta\CmfCore\Workshops\View\Events\PackageEvent;

/**
 * Class EventProvider
 *
 * @package RozaVerta\CmfCore\Event
 */
final class EventProvider implements Arrayable
{
	use GetIdentifierTrait;
	use ServiceTrait;

	/**
	 * @var \RozaVerta\CmfCore\App
	 */
	protected $app;

	/**
	 * @var \RozaVerta\CmfCore\Log\LogManager
	 */
	protected $log;

	private $name = '';

	private $completable = false;

	private $classes = [];

	/**
	 * EventProvider constructor.
	 *
	 * @param string    $name
	 * @param bool|null $completable
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function __construct( string $name, ? bool $completable = null )
	{
		$this->thisServices( "app", "log" );

		$this->name = trim($name);
		if( is_bool($completable) )
		{
			$this->completable = $completable;
		}

		// ready database only for install system

		if( $this->app->installed() )
		{
			$row = $this
				->app
				->db
				->plainBuilder()
				->from( Events_SchemeDesigner::getTableName() )
				->where('name', $this->name)
				->first( [ "id", "completable" ] );

			if( $row )
			{
				$this->setId( (int) $row["id"] );
				$this->completable = $this->app->db->convertToPHPValue( $row["completable"], Type::BOOLEAN );
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
				PackageEvent::eventName(),
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

		$rows = EventHandlerLinks_WithHandlers_SchemeDesigner::find()
			->where('event_id', $id)
			->get();

		/** @var EventHandlerLinks_WithHandlers_SchemeDesigner $row */
		foreach( $rows as $row )
		{
			$className = $row->getClassName();
			try {
				$ref = new ReflectionClass($className);
			} catch(\ReflectionException $e) {
				$this->log->line( $e->getMessage() );
				continue;
			}

			if( ! $ref->implementsInterface( EventPrepareInterface::class ) )
			{
				$this->log->line( "Class '{$className}' should implement the interface " . EventPrepareInterface::class );
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