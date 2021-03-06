<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.03.2019
 * Time: 22:36
 */

namespace RozaVerta\CmfCore\View\Interfaces;

use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;
use RozaVerta\CmfCore\Module\Interfaces\ModuleInterface;

interface PluginInterface extends ModuleGetterInterface
{
	/**
	 * PluginInterface constructor.
	 *
	 * @param ModuleInterface $module
	 * @param LexerInterface $lexer
	 */
	public function __construct( ModuleInterface $module, LexerInterface $lexer );

	static public function getPluginName(): string;
}