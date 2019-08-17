<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.05.2019
 * Time: 13:17
 */

namespace RozaVerta\CmfCore\Support;

use RozaVerta\CmfCore\Interfaces\VarExportInterface;
use RozaVerta\CmfCore\Traits\GetTrait;

/**
 * Class CollectionResult
 *
 * @package RozaVerta\CmfCore\Support
 */
class CollectionResult implements VarExportInterface
{
	use GetTrait;

	protected $offset = 0;

	protected $limit = 1;

	protected $total = 0;

	protected $items = [];

	protected $collection;

	/**
	 * CollectionResult constructor.
	 *
	 * @param Collection $collection
	 * @param int        $limit
	 * @param int        $offset
	 * @param int|null   $total
	 * @param array      $additional
	 */
	public function __construct( Collection $collection, int $limit = 0, int $offset = 0, ? int $total = null, array $additional = [])
	{
		$this->collection = $collection;

		if( $offset < 1 )
		{
			$offset = 0;
		}

		if( $limit < 1 )
		{
			$limit = $collection->count();
			if($limit < 1)
			{
				$limit = 1;
			}
		}

		if( is_null($total) )
		{
			$total = $offset + $collection->count();
		}
		else if( $total > $offset + $limit )
		{
			$total = $offset + $limit;
		}

		$this->offset = $offset;
		$this->limit = $limit;
		$this->total = $total;
		$this->items = $additional;
	}

	/**
	 * Get result collection
	 *
	 * @return Collection
	 */
	public function getCollection(): Collection
	{
		return $this->collection;
	}

	/**
	 * Get offset records count
	 *
	 * @return int
	 */
	public function getOffset(): int
	{
		return $this->offset;
	}

	/**
	 * Get records limit
	 *
	 * @return int
	 */
	public function getLimit(): int
	{
		return $this->limit;
	}

	/**
	 * Get total records
	 *
	 * @return int
	 */
	public function getTotal(): int
	{
		return $this->total;
	}

	/**
	 * Get current page
	 *
	 * @return int
	 */
	public function getPage(): int
	{
		if($this->offset < $this->limit)
		{
			return 1;
		}
		else
		{
			return 1 + floor($this->limit/$this->offset);
		}
	}

	/**
	 * Get all pages count
	 *
	 * @return int
	 */
	public function getPages(): int
	{
		return ceil($this->total / $this->limit);
	}

	/**
	 * @param array $data
	 * @return static
	 */
	public static function __set_state( $data )
	{
		return new static( $data["collection"], $data["limit"], $data["offset"], $data["total"], $data["items"] );
	}

	public function getArrayForVarExport(): array
	{
		return [
			"offset" => $this->offset,
			"limit" => $this->limit,
			"total" => $this->total,
			"items" => $this->items,
			"collection" => $this->collection,
		];
	}
}