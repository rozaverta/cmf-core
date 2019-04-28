<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.04.2019
 * Time: 10:47
 */

namespace RozaVerta\CmfCore\Workshops\Helper;

use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\DatabaseManager as DB;
use RozaVerta\CmfCore\Database\Query\Builder;
use RozaVerta\CmfCore\Database\Query\StateUpdateBuilder;
use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Exceptions\RuntimeException;

/**
 * Class PositionCursor
 *
 * @package RozaVerta\CmfCore\Workshops\Helper
 */
class PositionCursor
{
	public const MODE_BEFORE = 1;
	public const MODE_AFTER = 2;
	public const MODE_SWAP = 3;

	public const UPDATE_MODE_PRIMARY = 1;
	public const UPDATE_MODE_ALL = 2;

	private $tableName;

	private $column;

	private $where;

	private $step = 10;

	private $updateMode = self::UPDATE_MODE_PRIMARY;

	private $updateDateTimeColumns = [];

	private $updateData = [];

	/**
	 * PositionCursor constructor.
	 *
	 * @param string $tableName Table name
	 * @param string $column Position column name, the default is "position"
	 * @param array $where Conditions
	 */
	public function __construct(string $tableName, string $column = "position", array $where = [])
	{
		if( isset($where[$column]) )
		{
			unset($where[$column]);
		}

		$this->tableName = $tableName;
		$this->column = $column;
		$this->where = $where;
	}

	/**
	 * Set the position step, the default is 10
	 *
	 * @param int $step
	 * @return $this
	 */
	public function setStep( int $step )
	{
		if($step > 0)
		{
			$this->step = $step;
		}
		return $this;
	}

	/**
	 * Set update columns (datetime)
	 *
	 * @param string[] ...$columns
	 * @return $this
	 */
	public function setUpdateDatetimeColumns( ... $columns )
	{
		if(count($columns) === 1 && is_array($columns[0]))
		{
			$columns = $columns[0];
		}
		$this->updateDateTimeColumns = $columns;
		return $this;
	}

	/**
	 * Set update mode, primary only or secondary too
	 *
	 * @param int $mode
	 * @return $this
	 */
	public function setUpdateMode( int $mode )
	{
		if($mode === self::UPDATE_MODE_PRIMARY || $mode === self::UPDATE_MODE_ALL)
		{
			$this->updateMode = $mode;
		}
		return $this;
	}

	/**
	 * Set update data for primary query
	 *
	 * @param array $data
	 * @return PositionCursor
	 */
	public function setUpdateData( array $data )
	{
		$this->updateData = $data;
		return $this;
	}

	/**
	 * Get step
	 *
	 * @return int
	 */
	public function getStep(): int
	{
		return $this->step;
	}

	/**
	 * Get update columns (datetime columns)
	 *
	 * @return array
	 */
	public function getUpdateDatetimeColumns(): array
	{
		return $this->updateDateTimeColumns;
	}

	/**
	 * Get update data
	 *
	 * @return array
	 */
	public function getUpdateData(): array
	{
		return $this->updateData;
	}

	/**
	 * Get update mode
	 *
	 * @return int
	 */
	public function getUpdateMode(): int
	{
		return $this->updateMode;
	}

	/**
	 * Get next position
	 *
	 * @return int
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getNextPosition(): int
	{
		return $this->getLastPosition() + $this->step;
	}

	/**
	 * Get last position
	 *
	 * @return int
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getLastPosition(): int
	{
		$max = $this
			->getBuilder()
			->max($this->column);

		if( ! is_numeric($max) || $max < 1 )
		{
			return 0;
		}

		return (int) $max;
	}

	/**
	 * Get a recording position
	 *
	 * @param int $id
	 * @return int|null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function getPosition(int $id): ?int
	{
		$current = $this
			->getBuilder()
			->whereId($id)
			->value($this->column);

		return is_numeric($current) ? (int) $current : null;
	}

	/**
	 * Recalculate all duplicate positions by the specified column
	 *
	 * 1. change negative and zero positions
	 * 2. calculate duplicate positions
	 * 3. update duplicates in a loop
	 *
	 * @param string $column
	 * @return int
	 * @throws \Throwable
	 */
	public function restore(string $column = "id"): int
	{
		$changed = 0;

		// step 1. change negative and zero positions

		$all = $this
			->getBuilder()
			->where($this->column, "<", 1)
			->select(['id'])
			->orderBy($this->column)
			->addOrderBy($column)
			->get();

		$cnt = $all->count();
		if($cnt > 0)
		{
			$delta = $cnt * $this->step + $this->step;
			$next = $this->getNext(0);
			DB::connection()
				->transactional(function(Connection $connection) use ($all, $next, $delta, & $changed) {

					if( !is_null($next) )
					{
						$delta -= $next["position"];
						if($delta > 0)
						{
							$this
								->getBuilder($connection)
								->update(function(StateUpdateBuilder $state) use ($delta) {
									$this
										->mergeData($state)
										->expr($this->column, $this->column . ' + ' . $delta);
								});
						}
					}

					$updater = $this->getBuilder($connection);
					$state = $updater->getUpdateState();
					$this->mergeData($state, false);

					$position = $this->step;

					foreach($all as $item)
					{
						$state->set($this->column, $position);
						$updater
							->whereId((int) $item->get("id"))
							->update();

						$position += $this->step;
						++ $changed;
					}
				});
		}

		// step 2. calculate duplicate positions

		$all = $this
			->getBuilder()
			->select(['COUNT(id) as count_positions', $this->column])
			->having('count_positions', '>', 1)
			->groupBy($this->column)
			->get();

		// step 3. update duplicates in a loop

		if($all->count() > 0)
		{
			foreach($all as $item)
			{
				$changed += $this->restoreDuplicatePosition((int) $item->get($this->column), $column);
			}
		}

		return $changed;
	}

	/**
	 * Recalculate duplicates by the specified column for the specified position
	 *
	 * @param int $position
	 * @param string $column
	 * @return int
	 * @throws \Throwable
	 */
	public function restoreDuplicatePosition(int $position, string $column = "id"): int
	{
		$all = $this
			->getBuilder()
			->where($this->column, $position)
			->select(['id'])
			->orderBy($column)
			->get();

		$cnt = $all->count();
		if( $cnt < 2 )
		{
			return 0;
		}

		$delta = $cnt * $this->step;
		$next = $this->getNext($position);

		DB::connection()
			->transactional(function(Connection $connection) use ($all, $next, $delta, $position) {

				if( !is_null($next) )
				{
					$delta -= ($next["position"] - $position);
					if($delta > 0)
					{
						$this
							->getBuilder($connection)
							->where($this->column, '>', $position)
							->update(function(StateUpdateBuilder $state) use ($delta) {
								$this
									->mergeData($state)
									->expr($this->column, $this->column . ' + ' . $delta);
							});
					}
				}

				$updater = $this->getBuilder($connection);
				$state = $updater->getUpdateState();
				$this->mergeData($state, false);
				$first = true;

				foreach($all as $item)
				{
					if($first)
					{
						$first = false;
					}
					else
					{
						$state->set($this->column, $position);
						$updater
							->whereId((int) $item->get("id"))
							->update();
					}

					$position += $this->step;
				}
			});

		return $cnt - 1;
	}

	/**
	 * Move down
	 *
	 * @param int $id
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function down( int $id )
	{
		$position = $this->getPosition($id);
		if( is_null($position) )
		{
			$this->throwNotFound($id);
		}

		$next = $this->getNext($position);
		if( !is_null($next) )
		{
			$this->querySwap($id, $position, $next["id"], $next["position"], true);
		}
	}

	/**
	 * Move up
	 *
	 * @param int $id
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function up( int $id )
	{
		$position = $this->getPosition($id);
		if( is_null($position) )
		{
			$this->throwNotFound($id);
		}

		$next = $this->getPrev($position);
		if( !is_null($next) )
		{
			$this->querySwap($id, $position, $next["id"], $next["position"], true);
		}
	}

	/**
	 * Set after the specified entry
	 *
	 * @param int $id
	 * @param int $idAfter
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function after( int $id, int $idAfter )
	{
		$position = $this->getPosition($idAfter);
		if( is_null($position) )
		{
			$this->throwNotFound($idAfter);
		}

		$this->change($id, $position, self::MODE_AFTER);
	}

	/**
	 * Set before the specified entry
	 *
	 * @param int $id
	 * @param int $idBefore
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function before( int $id, int $idBefore )
	{
		$position = $this->getPosition($idBefore);
		if( is_null($position) )
		{
			$this->throwNotFound($idBefore);
		}

		$this->change($id, $position, self::MODE_AFTER);
	}

	/**
	 * Swap positions
	 * A warning! This method uses left and right records as PRIMARY and will use updated data for both objects.
	 *
	 * @param int $idLeft
	 * @param int $idRight
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function swap( int $idLeft, int $idRight )
	{
		$positionLeft = $this->getPosition($idLeft);
		$positionRight = $this->getPosition($idRight);

		if( is_null($positionLeft) )
		{
			$this->throwNotFound($idLeft);
		}

		if( is_null($positionRight) )
		{
			$this->throwNotFound($idRight);
		}

		$this->querySwap($idLeft, $positionLeft, $idRight, $positionRight);
	}

	/**
	 * Move entry to the beginning
	 *
	 * @param int $id
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function top(int $id)
	{
		$position = $this->getPosition($id);
		if( is_null($position) )
		{
			$this->throwNotFound($id);
		}

		$prev = $this->getPrev($position);
		if( ! is_null($prev) )
		{
			$first = $this
				->getBuilder()
				->orderBy($this->column)
				->value($this->column);

			if( ! is_numeric($first) )
			{
				$this->throwCalculatePosition();
			}

			$first = (int) $first;
			$delta = $position - $prev["position"];

			DB::connection()
				->transactional(function (Connection $connection) use ($id, $position, $first, $delta) {

					$this
						->getBuilder($connection)
						->where('id', '!=', $id)
						->where($this->column, '<', $position)
						->update(function(StateUpdateBuilder $state) use ($delta) {
							$this
								->mergeData($state)
								->expr($this->column, $this->column . ' + ' . $delta);
						});

					$this->querySetPosition($id, $first, $connection);
				});
		}
	}

	/**
	 * Move entry to end
	 *
	 * @param int $id
	 * @throws NotFoundException
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function end(int $id)
	{
		$position = $this->getPosition($id);
		if( is_null($position) )
		{
			$this->throwNotFound($id);
		}

		$next = $this->getNext($position);
		if(!is_null($next))
		{
			$last = $this->getLastPosition();
			$delta = $next["position"] - $position;

			DB::connection()
				->transactional(function (Connection $connection) use ($id, $position, $last, $delta) {

					$this
						->getBuilder($connection)
						->where('id', '!=', $id)
						->where($this->column, '>', $position)
						->update(function(StateUpdateBuilder $state) use ($delta) {
							$this
								->mergeData($state)
								->expr($this->column, $this->column . ' - ' . $delta);
						});

					$this->querySetPosition($id, $last, $connection);
				});
		}
	}

	/**
	 * Set new position for the specified entry
	 *
	 * @param int $id
	 * @param int $position
	 * @param int $mode
	 * @throws \Doctrine\DBAL\DBALException
	 * @throws \Throwable
	 */
	public function change( int $id, int $position, int $mode = self::MODE_BEFORE )
	{
		// rename for readability
		$idLeft = $id;
		$positionRight = $position;
		$positionLeft = $this->getPosition($id);

		if( is_null($positionLeft) )
		{
			$this->throwNotFound($idLeft);
		}

		$positionLeft = (int) $positionLeft;
		if( $positionLeft > 0 && $positionLeft === $positionRight )
		{
			return;
		}

		if($positionRight < 1)
		{
			$this->querySetPosition($idLeft, $this->getNextPosition());
		}
		else
		{
			// find position
			$idRight = $this
				->getBuilder()
				->where($this->column, $positionRight)
				->value('id');

			if( ! is_numeric($idRight) )
			{
				$this->querySetPosition($idLeft, $positionRight);
			}

			else if($mode === self::MODE_SWAP)
			{
				$this->querySwap($idLeft, $positionLeft, $idRight, $positionRight, true);
			}

			else if($mode === self::MODE_AFTER)
			{
				if( $positionLeft < $positionRight )
				{
					// 10
					// 20 <- this (change to 41)
					// 30
					// 40 <- after
					// 50
					$nextLeft = $this->getNext($positionLeft);
					if( is_null($nextLeft) )
					{
						$this->throwCalculatePosition();
					}

					if($nextLeft['id'] === $idRight)
					{
						$this->querySwap($idLeft, $positionLeft, $idRight, $positionRight, true);
					}
					else
					{
						$delta = $nextLeft['position'] - $positionLeft;
						$nextRight = $this->getNext($positionRight);

						DB::connection()
							->transactional(function (Connection $connection) use ($nextRight, $delta, $positionLeft, $positionRight, $idLeft) {

								$this->querySetPosition($idLeft, $positionRight, $connection);

								$b = $this
									->getBuilder($connection)
									->where('id', '!=', $idLeft)
									->where($this->column, '>', $positionLeft - 1);

								if( ! is_null($nextRight) )
								{
									$b->where($this->column, '<', $positionRight + 1);
								}

								$b->update(function(StateUpdateBuilder $state) use ($delta) {
									$this
										->mergeData($state)
										->expr($this->column, $this->column . ' - ' . $delta);
								});
							});
					}
				}
				else
				{
					// 10
					// 20 <- after
					// 30
					// 40 <- this (change to 21)
					// 50

					$nextRight = $this->getNext($positionRight);
					if( is_null($nextRight) )
					{
						$this->throwCalculatePosition();
					}

					if($nextRight['id'] !== $idLeft)
					{
						$nextLeft = $this->getNext($positionLeft);
						if(is_null($nextLeft))
						{
							$delta = $this->step;
						}
						else
						{
							$delta = $nextLeft["position"] - $positionLeft;
						}

						DB::connection()
							->transactional(function (Connection $connection) use ($idLeft, $nextRight, $positionLeft, $positionRight, $delta) {

								$this->querySetPosition($idLeft, $nextRight['position'], $connection);

								$this->getBuilder($connection)
									->where('id', '!=', $idLeft)
									->where($this->column, '>', $positionRight)
									->where($this->column, '<', $positionLeft + 1)
									->update(function(StateUpdateBuilder $state) use ($delta) {
										$this
											->mergeData($state)
											->expr($this->column, $this->column . ' + ' . $delta);
									});
							});
					}
					// else -> continue
				}
			}

			else if($mode === self::MODE_BEFORE)
			{
				if( $positionLeft < $positionRight )
				{
					// 10
					// 20 <- this (change to 39)
					// 30
					// 40 <- before
					// 50

					$prevRight = $this->getPrev($positionRight);
					if( is_null($prevRight) )
					{
						$this->throwCalculatePosition();
					}

					if($prevRight['id'] !== $idLeft)
					{
						$nextLeft = $this->getNext($positionLeft);
						if( is_null($nextLeft) )
						{
							$this->throwCalculatePosition();
						}

						$delta = $nextLeft["position"] - $positionLeft;

						DB::connection()
							->transactional(function (Connection $connection) use ($idLeft, $prevRight, $positionLeft, $positionRight, $delta) {

								$this->querySetPosition($idLeft, $prevRight["position"], $connection);

								$this
									->getBuilder($connection)
									->where("id", "!=", $idLeft)
									->where($this->column, '>', $positionLeft - 1)
									->where($this->column, '<', $positionRight)
									->update(function(StateUpdateBuilder $state) use ($delta) {
										$this
											->mergeData($state)
											->expr($this->column, $this->column . ' - ' . $delta);
									});
							});
					}

					// else -> continue
				}
				else
				{
					// 10
					// 20 <- before
					// 30
					// 40 <- this (change to 19)
					// 50

					$prevLeft = $this->getPrev($positionLeft);
					if( is_null($prevLeft) )
					{
						$this->throwCalculatePosition();
					}

					if($prevLeft["id"] === $idLeft)
					{
						$this->querySwap($idLeft, $positionLeft, $idRight, $positionRight, true);
					}
					else
					{
						$delta = $positionLeft - $prevLeft["position"];

						DB::connection()
							->transactional(function (Connection $connection) use ($idLeft, $positionLeft, $positionRight, $delta) {

								$this->querySetPosition($idLeft, $positionRight, $connection);

								$this
									->getBuilder($connection)
									->where("id", "!=", $idLeft)
									->where($this->column, '>', $positionRight - 1)
									->where($this->column, '<', $positionLeft + 1)
									->update(function(StateUpdateBuilder $state) use ($delta) {
										$this
											->mergeData($state)
											->expr($this->column, $this->column . ' + ' . $delta);
									});
							});
					}
				}
			}
		}
	}

	/**
	 * Add update columns for query
	 *
	 * @param StateUpdateBuilder $state
	 * @param bool $secondary
	 * @return StateUpdateBuilder
	 * @throws \Exception
	 */
	private function mergeData( StateUpdateBuilder $state, bool $secondary = true): StateUpdateBuilder
	{
		if( $secondary && $this->updateMode === self::UPDATE_MODE_PRIMARY )
		{
			return $state;
		}

		if( count($this->updateDateTimeColumns) )
		{
			$dateTime = new \DateTime();
			foreach( $this->updateDateTimeColumns as $column)
			{
				$state->set($column, $dateTime);
			}
		}

		if( ! $secondary && count($this->updateData) )
		{
			foreach($this->updateData as $column => $value)
			{
				$state->set($column, $value);
			}
		}

		return $state;
	}

	/**
	 * Get query builder
	 *
	 * @param Connection|null $connection
	 * @return Builder
	 */
	private function getBuilder(? Connection $connection = null): Builder
	{
		$builder = is_null($connection) ? DB::table($this->tableName) : $connection->table($this->tableName);
		if(count($this->where))
		{
			$builder->where($this->where);
		}
		return $builder;
	}

	/**
	 * Set one position for the specified entry
	 *
	 * @param int $id
	 * @param int $position
	 * @param Connection|null $connection
	 * @param bool $secondary
	 */
	private function querySetPosition(int $id, int $position, ? Connection $connection = null, bool $secondary = false)
	{
		$this
			->getBuilder($connection)
			->whereId($id)
			->update(
				function(StateUpdateBuilder $state) use ($position, $secondary) {
					$this
						->mergeData($state, $secondary)
						->set($this->column, $position);
				}
			);
	}

	/**
	 * Swap entries position
	 *
	 * @param int $idLeft
	 * @param int $positionLeft
	 * @param int $idRight
	 * @param int $positionRight
	 * @param bool $secondaryRight
	 * @throws \Throwable
	 */
	private function querySwap(int $idLeft, int $positionLeft, int $idRight, int $positionRight, bool $secondaryRight = false)
	{
		DB::connection()
			->transactional(function (Connection $connection) use($idLeft, $positionLeft, $idRight, $positionRight, $secondaryRight) {
				$this->querySetPosition($idLeft, $positionRight, $connection);
				$this->querySetPosition($idRight, $positionLeft, $connection, $secondaryRight);
			});
	}

	/**
	 * Get next entry (id and position column)
	 *
	 * @param int $position
	 * @return array|null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function getNext(int $position): ? array
	{
		return $this->getPrevNext(
			$this
				->getBuilder()
				->where($this->column, '>', $position)
				->orderBy($this->column)
		);
	}

	/**
	 * Get previous entry (id and position column)
	 *
	 * @param int $position
	 * @return array|null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function getPrev(int $position): ? array
	{
		return $this->getPrevNext(
			$this
				->getBuilder()
				->where($this->column, '<', $position)
				->orderBy($this->column, 'DESC')
		);
	}

	/**
	 * Create next/prev query
	 *
	 * @param Builder $builder
	 * @return array|null
	 * @throws \Doctrine\DBAL\DBALException
	 */
	private function getPrevNext(Builder $builder): ? array
	{
		$row = $builder
			->select(['id', $this->column])
			->first();

		if( !$row )
		{
			return null;
		}

		return ['id' => (int) $row->get("id"), 'position' => (int) $row->get("position")];
	}

	/**
	 * @param $id
	 * @throws NotFoundException
	 */
	private function throwNotFound($id)
	{
		throw new NotFoundException("The record '{$id}' was not found for the table '{$this->tableName}'");
	}

	/**
	 * @throws RuntimeException
	 */
	private function throwCalculatePosition()
	{
		throw new RuntimeException("Calculate position error");
	}
}