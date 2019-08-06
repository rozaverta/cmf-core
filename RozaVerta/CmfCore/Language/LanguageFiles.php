<?php

/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.09.2017
 * Time: 22:59
 */

namespace RozaVerta\CmfCore\Language;

use RozaVerta\CmfCore\Support\Prop;

class LanguageFiles extends Language
{
	protected function loadPackage( string $package_name )
	{
		$lines = Prop::file('language/' . $this->language . '/' . $package_name, $exists);
		return $exists ? $lines : false;
	}
}