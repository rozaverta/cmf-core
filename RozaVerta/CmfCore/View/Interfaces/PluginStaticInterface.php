<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 22:36
 */

namespace RozaVerta\CmfCore\View\Interfaces;

interface PluginStaticInterface extends PluginInterface
{
	public function render( array $data = [] ): string;
}