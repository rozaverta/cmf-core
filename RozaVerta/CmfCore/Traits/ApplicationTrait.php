<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.08.2018
 * Time: 2:39
 */

namespace RozaVerta\CmfCore\Traits;

use RozaVerta\CmfCore\App;

trait ApplicationTrait
{
	/**
	 * @var \RozaVerta\CmfCore\App
	 */
	protected $app;

	public function __construct()
	{
		$this->appInit();
	}

	protected function appInit()
	{
		$this->app = App::getInstance();
	}
}