<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.08.2018
 * Time: 23:01
 */

namespace RozaVerta\CmfCore\CliCommands;

use RozaVerta\CmfCore\Cli\AbstractCliCommand;

/**
 * Cmf update and remove system
 *
 * Class Cmf
 *
 * @package RozaVerta\CmfCore\CliCommands
 */
class Cmf extends AbstractCliCommand
{
	protected function exec()
	{
		$script = new Scripts\Cmf($this->getIO());
		$script->menu();
	}
}