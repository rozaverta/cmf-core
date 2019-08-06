<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.06.2019
 * Time: 9:03
 */

namespace RozaVerta\CmfCore\Database;

/**
 * Class Expression
 *
 * @package RozaVerta\CmfCore\Database
 */
class Expression
{
	/**
	 * The value of the expression.
	 *
	 * @var mixed
	 */
	protected $value;

	/**
	 * Create a new raw query expression.
	 *
	 * @param mixed $value
	 * @return void
	 */
	public function __construct( string $value )
	{
		$this->value = $value;
	}

	/**
	 * Get the value of the expression.
	 *
	 * @return string
	 */
	public function getValue(): string
	{
		return $this->value;
	}

	/**
	 * Get the value of the expression.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->getValue();
	}
}