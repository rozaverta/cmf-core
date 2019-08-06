<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 11:11
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class RoLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class RoLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return (1 == $number) ? 0 : (((0 == $number) || (($number % 100 > 0) && ($number % 100 < 20))) ? 1 : 2);
	}
}