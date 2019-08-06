<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 11:11
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class SlLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class SlLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return (1 == $number % 100) ? 0 : ((2 == $number % 100) ? 1 : (((3 == $number % 100) || (4 == $number % 100)) ? 2 : 3));
	}
}