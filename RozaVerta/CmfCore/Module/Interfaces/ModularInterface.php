<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 1:01
 */

namespace RozaVerta\CmfCore\Module\Interfaces;

interface ModularInterface
{
	/**
	 * Module for frontend
	 *
	 * @return bool
	 */
	public function isFront(): bool;

	/**
	 * Get module name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Get module key name
	 *
	 * @return string
	 */
	public function getKey(): string;

	/**
	 * Get module title
	 *
	 * @return string
	 */
	public function getTitle(): string;

	/**
	 * Module use router
	 *
	 * @return bool
	 */
	public function isRoute(): bool;

	/**
	 * Get module version
	 *
	 * @return string
	 */
	public function getVersion(): string;

	/**
	 * Get module path
	 *
	 * @return string
	 */
	public function getPathname(): string;

	/**
	 * Get module namespace
	 *
	 * @return string
	 */
	public function getNamespaceName(): string;

	/**
	 * Get all support addons
	 *
	 * @return array
	 */
	public function getSupport(): array;

	/**
	 * @return array
	 */
	public function getExtras(): array;

	/**
	 * Addons module is supported
	 *
	 * @param string $name
	 * @param null|string $version
	 * @return bool
	 */
	public function support( string $name, ?string $version = null ): bool;

	/**
	 * Convert Module Data to an Array
	 *
	 * @return array
	 */
	public function toArray(): array;
}