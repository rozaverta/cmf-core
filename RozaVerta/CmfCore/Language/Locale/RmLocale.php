<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 10:56
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class RmLocale
 *
 * Languages of the Romance group
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class RmLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return ((1 == $number % 10) && (11 != $number % 100)) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 10) || ($number % 100 >= 20))) ? 1 : 2);
	}
}