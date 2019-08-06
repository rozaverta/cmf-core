<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 10:53
 */

namespace RozaVerta\CmfCore\Language\Locale;

use RozaVerta\CmfCore\Language\Interfaces\ChoiceLocaleInterface;

class Dumper
{
	public static function getLocale( string $locale ): ChoiceLocaleInterface
	{
		switch ('pt_BR' !== $locale && \strlen($locale) > 3 ? substr($locale, 0, strrpos($locale, '_')) : $locale)
		{
			case 'af':
			case 'bn':
			case 'bg':
			case 'ca':
			case 'da':
			case 'de':
			case 'el':
			case 'en':
			case 'eo':
			case 'es':
			case 'et':
			case 'eu':
			case 'fa':
			case 'fi':
			case 'fo':
			case 'fur':
			case 'fy':
			case 'gl':
			case 'gu':
			case 'ha':
			case 'he':
			case 'hu':
			case 'is':
			case 'it':
			case 'ku':
			case 'lb':
			case 'ml':
			case 'mn':
			case 'mr':
			case 'nah':
			case 'nb':
			case 'ne':
			case 'nl':
			case 'nn':
			case 'no':
			case 'oc':
			case 'om':
			case 'or':
			case 'pa':
			case 'pap':
			case 'ps':
			case 'pt':
			case 'so':
			case 'sq':
			case 'sv':
			case 'sw':
			case 'ta':
			case 'te':
			case 'tk':
			case 'ur':
			case 'zu':
				return new EnLocale($locale);

			case 'am':
			case 'bh':
			case 'fil':
			case 'fr':
			case 'gun':
			case 'hi':
			case 'hy':
			case 'ln':
			case 'mg':
			case 'nso':
			case 'pt_BR':
			case 'ti':
			case 'wa':
				return new FrLocale($locale);

			case 'be':
			case 'bs':
			case 'hr':
			case 'ru':
			case 'sh':
			case 'sr':
			case 'uk':
				return new RmLocale($locale);

			case 'cs':
			case 'sk':
				return new CsLocale($locale);

			case 'ga':
				return new GaLocale($locale);

			case 'lt':
				return new LtLocale($locale);

			case 'sl':
				return new SlLocale($locale);

			case 'mk':
				return new MkLocale($locale);

			case 'mt':
				return new MtLocale($locale);

			case 'lv':
				return new LvLocale($locale);

			case 'pl':
				return new PlLocale($locale);

			case 'cy':
				return new CyLocale($locale);

			case 'ro':
				return new RoLocale($locale);

			case 'ar':
				return new ArLocale($locale);

			default:
				return new ZeroLocale($locale);
		}
	}
}