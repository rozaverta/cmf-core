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
 * Module register, install, update methods
 *
 * Class Module
 *
 * @package RozaVerta\CmfCore\CliCommands
 */
class Module extends AbstractCliCommand
{
	/**
	 * @throws \Throwable
	 */
	protected function exec()
	{
		$script = new Scripts\Module($this->getIO());
		$script->menu();
	}
}