<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 11:11
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class LvLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class LvLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return (0 == $number) ? 0 : (((1 == $number % 10) && (11 != $number % 100)) ? 1 : 2);
	}
}