<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 10:56
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class FrLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class FrLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return ((0 == $number) || (1 == $number)) ? 0 : 1;
	}
}