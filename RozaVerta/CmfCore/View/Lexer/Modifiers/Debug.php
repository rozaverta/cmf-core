<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\View\Interfaces\ModifierInterface;

class Debug implements ModifierInterface
{
	public function format( $value, array $attributes = [] )
	{
		$flag = current($attributes);

		if(is_string($flag) && strtolower($flag) === 'dump')
		{
			ob_start();
			var_dump($value);
			$result = ob_get_contents();
			ob_end_clean();
		}
		else
		{
			$result = print_r($value);
		}

		return '<pre>' . $result . '</pre>';
	}
}