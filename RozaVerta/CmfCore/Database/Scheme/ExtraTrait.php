<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 19:43
 */

namespace RozaVerta\CmfCore\Database\Scheme;

use RozaVerta\CmfCore\Support\Prop;

trait ExtraTrait
{
	/**
	 * @var Prop
	 */
	protected $extra = null;

	/**
	 * Get extra item
	 *
	 * @param string $name
	 * @param mixed $default
	 * @return mixed
	 */
	public function extra(string $name, $default = null)
	{
		return $this->extra === null ? $default : $this->extra->getOr($name, $default);
	}

	/**
	 * @return Prop
	 */
	public function getExtras(): ?Prop
	{
		return $this->extra;
	}
}