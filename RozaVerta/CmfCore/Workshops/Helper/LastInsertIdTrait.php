<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.04.2019
 * Time: 16:35
 */

namespace RozaVerta\CmfCore\Workshops\Helper;

trait LastInsertIdTrait
{
	private $lastInsertId = null;

	public function getLastInsertId(): ? int
	{
		return $this->lastInsertId;
	}

	protected function setLastInsertId( int $id )
	{
		$this->lastInsertId = $id;
	}

	protected function clearLastInsertId()
	{
		$this->lastInsertId = null;
	}
}