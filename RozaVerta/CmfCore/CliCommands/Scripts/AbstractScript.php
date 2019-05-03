<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.08.2018
 * Time: 17:15
 */

namespace RozaVerta\CmfCore\CliCommands\Scripts;

use RozaVerta\CmfCore\Cli\Traits\SystemHostTrait;
use RozaVerta\CmfCore\Cli\IO\InputOutputInterface;
use RozaVerta\CmfCore\Traits\ApplicationTrait;

/**
 * Class AbstractScript
 *
 * @package RozaVerta\CmfCore\CliCommands\Scripts
 */
abstract class AbstractScript
{
	use SystemHostTrait;
	use ApplicationTrait;

	public function __construct( InputOutputInterface $IO )
	{
		$this->appInit();
		$this->setIO($IO);
		$this->init();
	}

	protected function init() {}

	protected function goodBy( string $message = "" )
	{
		if( !$message )
		{
			$message = "Good by! o_<";
		}
		$this->getIO()->write("<info>\$</info> {$message}");
	}

	protected function isContinue()
	{
		return $this->getIO()->confirm("Continue (<info>y</info>/n)? ");
	}
}