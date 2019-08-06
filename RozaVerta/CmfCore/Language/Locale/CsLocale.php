<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 11:11
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class CsLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class CsLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return (1 == $number) ? 0 : ((($number >= 2) && ($number <= 4)) ? 1 : 2);
	}
}