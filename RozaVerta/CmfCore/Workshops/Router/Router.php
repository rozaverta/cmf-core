<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.04.2019
 * Time: 21:17
 */

namespace RozaVerta\CmfCore\Workshops\Router;

use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Event\Exceptions\EventAbortException;
use RozaVerta\CmfCore\Route\Exceptions\MountPointNotFoundException;
use RozaVerta\CmfCore\Schemes\ContextRouterLinks_SchemeDesigner;
use RozaVerta\CmfCore\Schemes\Routers_SchemeDesigner;
use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Workshops\Helper\LastInsertIdTrait;
use RozaVerta\CmfCore\Workshops\Helper\PositionCursor;
use RozaVerta\CmfCore\Workshops\Helper\Tool;

/**
 * Class Router
 *
 * @package RozaVerta\CmfCore\Workshops\Router
 */
class Router extends Workshop
{
	use LastInsertIdTrait;

	private $positionMode = PositionCursor::MODE_BEFORE;

	/**
	 * Set position mode, see more in the \RozaVerta\CmfCore\Workshops\Helper\PositionCursor class
	 *
	 * @param int $mode use PositionCursor::MODE_BEFORE or PositionCursor::MODE_AFTER or PositionCursor::MODE_SWAP
	 * @return $this
	 */
	public function setPositionMode(int $mode)
	{
		if(in_array($mode, [PositionCursor::MODE_BEFORE, PositionCursor::MODE_AFTER, PositionCursor::MODE_SWAP]))
		{
			$this->positionMode = $mode;
		}
		return $this;
	}

	/**
	 * Mount
	 *
	 * @param string $name
	 * @param string $path
	 * @param int|null $position
	 * @param array|null $properties
	 *
	 * @throws Exceptions\RouterValidateException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function mount(string $name, string $path, ? int $position = null, ? array $properties = null)
	{
		$this->clearLastInsertId();
		$this->verifyName($name);
		$path = trim($path);
		$position = is_null($position) || $position < 1 ? 0 : $position;

		$event = new Events\MountEvent($this, "mount", [
			"name" => $name,
			"path" => $path,
			"position" => $position
		]);

		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException("Mount aborted" );
		}

		if($event->name !== $name)
		{
			$name = $event->name;
			$this->verifyName($name);
		}

		$this
			->db
			->transactional(function (Connection $connection) use ($name, $event) {

				$table = Routers_SchemeDesigner::getTableName();
				$positionHelper = new PositionCursor($table);
				$connection
					->table($table)
					->insert([
						"name" => $name,
						"path" => $event->path,
						"position" => $event->position < 1 ? $positionHelper->getNextPosition() : 0,
						"module_id" => $this->getModuleId(),
						"properties" => []
					]);

				$id = $connection->lastInsertId();

				if($event->position > 0)
				{
					$positionHelper->change($id, $event->position, $this->positionMode);
				}

				$this->setLastInsertId($id);
			});

		$id = $this->getLastInsertId();
		$dispatcher->complete($id);

		if( is_array($properties) && count($properties) )
		{
			$this->properties($id, [], $properties);
		}
	}

	/**
	 * Unmount
	 *
	 * @param int $id
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function unmount(int $id)
	{
		$this->clearLastInsertId();
		try {
			$scheme = $this->getScheme( $id );
		}
		catch( MountPointNotFoundException $e ) {
			return;
		}

		$event = new Events\UnmountEvent($this, "unmount", [
			"id" => $id,
			"name" => $scheme->getName(),
			"path" => $scheme->getPath(),
			"position" => $scheme->getPosition(),
			"properties" => $scheme->getProperties()
		]);

		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException("Unmount aborted" );
		}

		$this
			->db
			->transactional(function (Connection $connection) use ($id) {

				$connection
					->table(Routers_SchemeDesigner::getTableName())
					->whereId($id)
					->delete();

				$connection
					->table(ContextRouterLinks_SchemeDesigner::getTableName())
					->where("router_id", $id)
					->delete();
			});

		$dispatcher->complete();
	}

	/**
	 * Update mount point
	 *
	 * @param int $id
	 * @param string $name
	 * @param string $path
	 * @param int|null $position
	 * @param array|null $properties
	 *
	 * @throws MountPointNotFoundException
	 * @throws Exceptions\RouterValidateException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function update(int $id, string $name, string $path, ? int $position = null, ? array $properties = null)
	{
		$this->clearLastInsertId();
		$scheme = $this->getScheme($id);

		$this->verifyName($name, $id);
		$path = trim($path);
		$position = is_null($position) || $position < 1 ? $scheme->getPosition() : $position;

		if( $name !== $scheme->getName() ||
			$path !== $scheme->getPath() ||
			$position !== $scheme->getPosition()
		)
		{
			// update
			$path = trim($path);
			$position = is_null($position) || $position < 1 ? 0 : $position;

			$event = new Events\MountPointUpdateEvent($this, "update", [
				"id" => $id,
				"oldName" => $scheme->getName(),
				"oldPath" => $scheme->getPath(),
				"oldPosition" => $scheme->getPosition(),
				"name" => $name,
				"path" => $path,
				"position" => $position
			]);

			$dispatcher = $this->event->dispatcher($event->getName());
			$dispatcher->dispatch($event);

			if( $event->isPropagationStopped() )
			{
				throw new EventAbortException("Update mount point aborted" );
			}

			if($event->name !== $name)
			{
				$name = $event->name;
				$this->verifyName($name);
			}

			$table = Routers_SchemeDesigner::getTableName();
			$updateData = [];
			if($name !== $scheme->getName()) $updateData["name"] = $name;
			if($event->path !== $scheme->getPath()) $updateData["path"] = $event->path;

			if($event->position !== $scheme->getPosition())
			{
				$positionHelper = new PositionCursor($table);
				$positionHelper
					->setUpdateData($updateData)
					->change($id, $event->position, $this->positionMode);
			}
			else if(count($updateData))
			{
				$this
					->db
					->table($table)
					->whereId($id)
					->update($updateData);
			}

			$dispatcher->complete($id);
		}

		if( is_array($properties) )
		{
			$this->properties($id, $scheme->getProperties(), $properties);
		}
	}

	/**
	 * Update properties for specified mount point
	 *
	 * @param int $id
	 * @param array $properties
	 * @throws MountPointNotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function updateProperties(int $id, array $properties)
	{
		$this->clearLastInsertId();
		$scheme = $this->getScheme($id);
		$this->properties($id, $scheme->getProperties(), $properties);
	}

	/**
	 * Get point database scheme
	 *
	 * @param int $id
	 * @return Routers_SchemeDesigner
	 * @throws MountPointNotFoundException
	 * @throws Exceptions\RouterValidateException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function getScheme(int $id): Routers_SchemeDesigner
	{
		/** @var Routers_SchemeDesigner $row */
		$row = $this
			->db
			->table(Routers_SchemeDesigner::class)
			->whereId($id)
			->first();

		if( ! $row )
		{
			throw new MountPointNotFoundException("The '{$id}' mount point not found");
		}

		if( $row->getModuleId() !== $this->getModuleId() )
		{
			throw new Exceptions\RouterValidateException("The specified mount point belongs to another module.", "access_module");
		}

		return $row;
	}

	/**
	 * Update properties
	 *
	 * @param int $id
	 * @param array $old
	 * @param array $new
	 * @throws \Throwable
	 */
	private function properties(int $id, array $old, array $new)
	{
		if( Tool::compareProperties($old, $new) )
		{
			return;
		}

		$event = new Events\MountPointPropertiesEvent($this, "properties", [
			"id" => $id,
			"oldProperties" => $old,
			"properties" => $new
		]);

		$dispatcher = $this->event->dispatcher($event->getName());
		$dispatcher->dispatch($event);

		if( $event->isPropagationStopped() )
		{
			throw new EventAbortException("Update mount point aborted" );
		}

		$this
			->db
			->table(Routers_SchemeDesigner::getTableName())
			->whereId($id)
			->update([
				"properties" => $event->properties
			]);

		$dispatcher->complete($id);
	}

	/**
	 * Checks mount point name
	 *
	 * @param string $name
	 * @param int $id
	 * @return bool
	 * @throws Exceptions\RouterValidateException
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function verifyName( string & $name, int $id = 0 ): bool
	{
		$name = trim($name);
		$len = strlen($name);
		if( !$len )
		{
			throw new Exceptions\RouterValidateException("Empty mount point name.", "name");
		}

		if( $len > 255 )
		{
			throw new Exceptions\RouterValidateException("The mount point name is too long. The maximum length is 255 characters.", "name");
		}

		$name = str_replace([" ", "-"], "_", $name);
		$valid = ! preg_match('/[^a-z0-9_]/', $name) && ctype_alpha($name[0]) && strpos($name, "__") === 0 && ctype_alnum($name[$len-1]);
		if( !$valid )
		{
			throw new Exceptions\RouterValidateException("Invalid mount point name. Use a-z signs, numbers, and underscores.", "name");
		}

		$builder = $this
			->db
			->table(Routers_SchemeDesigner::getTableName())
			->where("name", $name);

		if($id)
		{
			$builder->where("id", "!=", $id);
		}

		if($builder->count('id') > 0)
		{
			throw new Exceptions\RouterValidateException("The specified mount point name '{$name}' is already in use.", "name");
		}

		return $valid;
	}
}