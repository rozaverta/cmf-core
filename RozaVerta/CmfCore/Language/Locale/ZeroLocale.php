<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 11:11
 */

namespace RozaVerta\CmfCore\Language\Locale;

/**
 * Class ZeroLocale
 *
 * @package RozaVerta\CmfCore\Language\Locale
 */
class ZeroLocale extends Locale
{
	public function getRule( int $number ): int
	{
		return 0;
	}
}