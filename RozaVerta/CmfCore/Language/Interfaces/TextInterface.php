<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.09.2017
 * Time: 23:01
 */

namespace RozaVerta\CmfCore\Language\Interfaces;

interface TextInterface
{
	public function text( string $key, string $context = "default" ): string;
}