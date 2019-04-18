<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 20:38
 */

namespace RozaVerta\CmfCore\Database\Query;

class StateInsertBuilder extends AbstractState
{
	protected function setBuilder( string $column, $value )
	{
		$this
			->dbalBuilder
			->setValue($column, $value);
	}

	protected function getCompleteHookName(): string
	{
		return "onInsert";
	}
}