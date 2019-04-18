<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 10:27
 */

namespace RozaVerta\CmfCore\Database\Interfaces;

interface ColumnHookInterface
{
	public function __construct( string $tableName, string $column );

	public function onInsert();

	public function onUpdate();

	public function onPrepareValue( $value );
}