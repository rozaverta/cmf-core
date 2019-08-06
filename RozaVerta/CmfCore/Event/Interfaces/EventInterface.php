<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 21.08.2015
 * Time: 19:13
 */

namespace RozaVerta\CmfCore\Event\Interfaces;

/**
 * Interface EventInterface
 *
 * @package RozaVerta\CmfCore\Event\Interfaces
 */
interface EventInterface
{
	/**
	 * Get event name.
	 *
	 * @return string
	 */
	static public function eventName(): string;

	/**
	 * Get event name.
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Get all events parameters.
	 *
	 * @return array
	 */
	public function getParams(): array;

	/**
	 * Get event parameter by name.
	 *
	 * @param string $name parameter name
	 * @return mixed
	 */
	public function getParam( string $name );

	/**
	 * Prevents the event from being passed to further listeners.
	 *
	 * @return mixed
	 */
	public function stopPropagation();

	/**
	 * Checks if stopPropagation has been called.
	 *
	 * @return bool
	 */
	public function isPropagationStopped(): bool;
}