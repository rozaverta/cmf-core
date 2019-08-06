<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2019
 * Time: 10:39
 */

namespace RozaVerta\CmfCore\View\Interfaces;

use RozaVerta\CmfCore\View\View;

interface ExtenderInterface
{
	public function __construct(string $name, View $view);
}