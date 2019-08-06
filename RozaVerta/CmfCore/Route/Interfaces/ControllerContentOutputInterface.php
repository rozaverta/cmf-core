<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2017
 * Time: 13:29
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

/**
 * Interface ControllerContentOutputInterface
 *
 * @package RozaVerta\CmfCore\Route\Interfaces
 */
interface ControllerContentOutputInterface
{
	/**
	 * Render content is raw output.
	 *
	 * @return boolean
	 */
	public function isRaw(): bool;

	/**
	 * Render content.
	 *
	 * @return void
	 */
	public function output();

	/**
	 * Get content type
	 *
	 * @return string
	 */
	public function getContentType(): string;
}