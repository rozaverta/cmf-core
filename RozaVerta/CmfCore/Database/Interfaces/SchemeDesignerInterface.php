<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 06.08.2018
 * Time: 18:50
 */

namespace RozaVerta\CmfCore\Database\Interfaces;

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
}