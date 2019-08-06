<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.08.2019
 * Time: 22:16
 */

namespace RozaVerta\CmfCore\Cache\Database;

use Doctrine\DBAL\Types\Type;
use RozaVerta\CmfCore\Database\Connection;

/**
 * Class DatabaseEntity
 *
 * @package RozaVerta\CmfCore\Cache\Database
 */
class DatabaseEntity
{
	protected $items = [];

	/**
	 * DatabaseEntity constructor.
	 *
	 * @param array           $items
	 * @param Connection|null $connection
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function __construct( array $items, ? Connection $connection = null )
	{
		if( $connection !== null )
		{
			$items["id"] = (int) $items["id"];
			$items["updated_at"] = Type::getType( Type::DATETIME )->convertToPHPValue( $items["properties"], $connection->getDbalDatabasePlatform() );
		}
		$this->items = $items;
	}

	/**
	 * @return mixed
	 */
	public function getId(): int
	{
		return $this->items["id"];
	}

	/**
	 * @return mixed
	 */
	public function getValue()
	{
		return $this->items["value"];
	}

	/**
	 * @return mixed
	 */
	public function getUpdatedAt(): \DateTime
	{
		return $this->items["updated_at"];
	}
}