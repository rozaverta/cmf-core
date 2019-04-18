<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 10:58
 */

namespace RozaVerta\CmfCore\Language\Locale;

use RozaVerta\CmfCore\Language\Interfaces\ChoiceLocaleInterface;

/**
 * Class Locale
 * @package RozaVerta\CmfCore\Language\Locale
 */
abstract class Locale implements ChoiceLocaleInterface
{
	protected $locale;

	protected $names = [];

	/**
	 * @var null | \Closure
	 */
	protected $names_default = null;

	public function __construct( string $locale )
	{
		$this->locale = $locale;
	}

	public function setDefaultNameRule( \Closure $rule )
	{
		$this->names_default = $rule;
		return $this;
	}

	public function setNameRule( string $name, \Closure $rule )
	{
		$this->names[$name] = $rule;
		return $this;
	}

	public function getNameRule( string $name, int $number ): string
	{
		if( array_key_exists($name, $this->names) )
		{
			return $this->names[$name]( $number, $name, $this->locale );
		}
		else if( is_null($this->names_default) )
		{
			return $name;
		}
		else
		{
			return ($this->names_default)( $name, $number, $this->locale );
		}
	}

	/**
	 * @return string
	 */
	public function getLocale(): string
	{
		return $this->locale;
	}
}