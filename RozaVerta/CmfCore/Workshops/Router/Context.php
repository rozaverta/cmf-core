<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.04.2019
 * Time: 21:17
 */

namespace RozaVerta\CmfCore\Workshops\Router;

use RozaVerta\CmfCore\Support\Workshop;
use RozaVerta\CmfCore\Workshops\Helper\LastInsertIdTrait;

class Context extends Workshop
{
	use LastInsertIdTrait;

	public function create(array $data, array $properties = [])
	{
		//
	}

	public function update(int $id, array $data, array $properties = [])
	{
		//
	}

	public function updateProperties(int $id, array $properties)
	{
		//
	}

	public function delete(int $id)
	{
		//
	}

	public function link(int $contextId, int $mountPointId)
	{
		//
	}

	public function unlink(int $contextId, int $mountPointId)
	{
		//
	}
}