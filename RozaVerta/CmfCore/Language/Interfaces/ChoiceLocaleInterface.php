<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.09.2018
 * Time: 10:56
 */

namespace RozaVerta\CmfCore\Language\Interfaces;

use Closure;

interface ChoiceLocaleInterface
{
	public function getLocale(): string;

	public function setNameRule( string $name, Closure $rule );

	public function getNameRule( string $name, int $number ): string;

	public function getRule( int $number ): int;
}