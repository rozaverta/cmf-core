<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.08.2018
 * Time: 18:50
 */

namespace RozaVerta\CmfCore\Database\Interfaces;

use RozaVerta\CmfCore\Database\Query\Builder;

interface SchemeDesignerInterface
{
	static public function __set_state( $data );

	/**
	 * Get table name
	 *
	 * @return string
	 */
	static public function getTableName(): string;

	/**
	 * Get schema for query builder
	 *
	 * @return array
	 */
	static public function getSchemaBuilder(): array;

	/**
	 * Create query builder for current table
	 *
	 * @param string|null $alias
	 * @param string|null $connection
	 * @return Builder
	 */
	static public function find( ? string $alias = null, ? string $connection = null ): Builder;
}