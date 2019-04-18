<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 20.03.2019
 * Time: 15:04
 */

namespace RozaVerta\CmfCore\Host\Interfaces;

use RozaVerta\CmfCore\Interfaces\Arrayable;

interface HostInterface extends Arrayable
{
	/**
	 * Domain Hostname
	 *
	 * @return string
	 */
	public function getHostname(): string;

	/**
	 * Port
	 *
	 * @return int
	 */
	public function getPort(): int;

	/**
	 * Use ssl protocol
	 *
	 * @return bool
	 */
	public function isSsl(): bool;
}