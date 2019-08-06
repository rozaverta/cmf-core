<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:54
 */

namespace RozaVerta\CmfCore\Cache\Database;

use RozaVerta\CmfCore\Cache\DatabaseHash;
use RozaVerta\CmfCore\Cache\Hash;
use RozaVerta\CmfCore\Cache\Driver;
use RozaVerta\CmfCore\Database\Connection;
use RozaVerta\CmfCore\Database\Query\Builder;

class DatabaseDriver extends Driver
{
	use DatabaseConnectionTrait;

	private $ready = false;

	/**
	 * @var null | DatabaseEntity
	 */
	private $row = null;

	public function __construct( Connection $connection, string $table, Hash $hash )
	{
		if( ! $hash instanceof DatabaseHash )
		{
			throw new \InvalidArgumentException("You must used the " . DatabaseHash::class . ' object instance for the ' . __CLASS__ . ' constructor');
		}

		parent::__construct( $hash );

		$this->setConnection($connection, $table);
	}

	public function has(): bool
	{
		if( ! $this->ready )
		{
			$this->ready = true;

			/** @var DatabaseEntity $row */
			$row = $this->fetch( function( Builder $builder ) {
				return $builder
					->setResultClassName( DatabaseEntity::class )
					->select( [ 'id', 'value', 'updated_at', ] )
					->first();
			}, $this->builderThen() );

			if( !$row )
			{
				return false;
			}

			if( $this->life > 0 && $row->getUpdatedAt()->getTimestamp() + $this->life < time() )
			{
				$this->forget();
			}
			else
			{
				$this->row = $row;
			}
		}

		return ! is_null($this->row);
	}

	public function set( string $value ): bool
	{
		$data = [
			'value' => $value,
			'size' => strlen($value),
			'updated_at' => new \DateTime(),
		];

		if( $this->has() )
		{
			$id = $this->row->getId();
			$update = $this->fetch(
				function( Builder $builder ) use ( $data ) {
					return $builder->update( $data ) !== false;
				},
				$this->builderThen( false, $id )
			);

			if( !$update )
			{
				return false;
			}
		}
		else
		{
			$data["name"]   = $this->hash->keyName();
			$data["prefix"] = $this->hash->keyPrefix();

			$id = $this->fetch(
				function( Builder $builder ) use ( $data ) {
					return $builder->insertGetId( $data );
				},
				$this->builderThen( false )
			);

			if( !$id )
			{
				return false;
			}
		}

		$row = new DatabaseEntity( [
			"id" => $id,
			"value" => $value,
			"updated_at" => $data["updated_at"],
		] );

		$this->row = $row;
		$this->ready = true;

		return true;
	}

	protected function exportData( $data ): bool
	{
		return $this->set(serialize($data));
	}

	public function get()
	{
		return $this->has() ? $this->row->getValue() : null;
	}

	public function import()
	{
		return $this->has() ? unserialize( $this->row->getValue() ) : null;
	}

	public function forget(): bool
	{
		$delete = $this->fetch(
			function( Builder $builder ) {
				return $builder->delete() !== false;
			},
			$this->builderThen()
		);

		if( !$delete )
		{
			return false;
		}

		$this->ready = false;
		$this->row = null;

		return true;
	}

	protected function builderThen( bool $hash = true, int $whereId = 0 ): Builder
	{
		$builder = $this->builder();

		if($hash)
		{
			$builder
				->where('name', '=', $this->hash->keyName())
				->where('prefix', '=', $this->hash->keyPrefix());
		}
		else if( $whereId > 0 )
		{
			$builder
				->whereId( $whereId );
		}

		return $builder;
	}
}