<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 18:58
 */

namespace RozaVerta\CmfCore\Cli\Traits;

use RozaVerta\CmfCore\Cli\IO\InputOutputInterface;

trait IOTrait
{
	/**
	 * @var InputOutputInterface | null
	 */
	private $IO = null;

	protected function setIO($IO)
	{
		$this->IO = $IO;
	}

	/**
	 * @return InputOutputInterface
	 */
	protected function getIO(): InputOutputInterface
	{
		return $this->IO;
	}

	/**
	 * @return bool
	 */
	protected function hasIO(): bool
	{
		return ! is_null($this->IO);
	}
}