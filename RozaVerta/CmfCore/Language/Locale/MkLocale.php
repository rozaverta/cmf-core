<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 11:11
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class MkLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class MkLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return (1 == $number % 10) ? 0 : 1;
	}
}