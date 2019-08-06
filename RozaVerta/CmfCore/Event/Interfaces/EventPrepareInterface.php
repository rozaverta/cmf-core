<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 19:13
 */

namespace RozaVerta\CmfCore\Event\Interfaces;

use RozaVerta\CmfCore\Event\Dispatcher;

interface EventPrepareInterface
{
	/**
	 * Get event parameter by name
	 *
	 * @param Dispatcher $manager
	 */
	public function prepare( Dispatcher $manager );
}