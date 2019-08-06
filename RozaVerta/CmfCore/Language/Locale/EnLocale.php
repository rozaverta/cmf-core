<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 10:56
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class EnLocale
 *
 * Languages of the English group
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class EnLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return (1 == $number) ? 0 : 1;
	}
}