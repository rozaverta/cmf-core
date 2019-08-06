<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 26.08.2018
 * Time: 22:57
 */

namespace RozaVerta\CmfCore\Cache\Properties;

class PropertyLog extends Property
{
	/**
	 * @return bool
	 */
	public function isError()
	{
		return strtolower($this->name) === "error";
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}
}