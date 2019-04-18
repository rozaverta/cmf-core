<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 1:11
 */

namespace RozaVerta\CmfCore\Module\Interfaces;

interface ResourceInterface extends ModuleGetterInterface
{
	/**
	 * Get resource type
	 *
	 * @return string
	 */
	public function getType(): string;

	/**
	 * Compare resource type
	 *
	 * @param string $type
	 *
	 * @return bool
	 */
	public function hasType( string $type ): bool;

	/**
	 * Get the resource file path without filename
	 *
	 * @return string
	 */
	public function getPath(): string;

	/**
	 * Get the resource name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Get the path to the resource file
	 *
	 * @return string
	 */
	public function getPathname(): string;

	/**
	 * Get raw content
	 *
	 * @return string
	 */
	public function getRawData(): string;
}