<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 11:11
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class GaLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class GaLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return (1 == $number) ? 0 : ((2 == $number) ? 1 : 2);
	}
}