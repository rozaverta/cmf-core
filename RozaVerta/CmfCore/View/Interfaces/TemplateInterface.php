<?php

namespace RozaVerta\CmfCore\View\Interfaces;

use RozaVerta\CmfCore\Interfaces\Arrayable;

interface TemplateInterface extends Arrayable
{
	/**
	 * Template name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Template file pathname
	 *
	 * @return string
	 */
	public function getPathname(): string;

	/**
	 * Template properties
	 *
	 * @return array
	 */
	public function getProperties(): array;

	/**
	 * Get template package
	 *
	 * @return PackageInterface
	 */
	public function getPackage(): PackageInterface;

	/**
	 * Get template package ID
	 *
	 * @return int
	 */
	public function getPackageId(): int;
}