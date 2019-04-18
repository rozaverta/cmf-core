<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 11:11
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class PlLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class PlLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return (1 == $number) ? 0 : ((($number % 10 >= 2) && ($number % 10 <= 4) && (($number % 100 < 12) || ($number % 100 > 14))) ? 1 : 2);
	}
}