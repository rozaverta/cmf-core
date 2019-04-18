<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 0:32
 */

namespace RozaVerta\CmfCore\Interfaces;

interface ThrowableInterface extends \Throwable
{
	/**
	 * Set the Exception code name
	 *
	 * @param mixed $name
	 * @return string
	 */
	public function setCodeName( $name );

	/**
	 * Gets the Exception code name, based on class name by default.
	 *
	 * @return string
	 */
	public function getCodeName(): string;
}