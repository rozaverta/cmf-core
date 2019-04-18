<?php

namespace RozaVerta\CmfCore\View\Interfaces;

use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;

interface PackageInterface extends ModuleGetterInterface, Arrayable
{
	/**
	 * Package ID
	 *
	 * @return int
	 */
	public function getId(): int;

	/**
	 * Package name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * Package description
	 *
	 * @return string
	 */
	public function getDescription(): string;

	/**
	 * Package version, default 1.0
	 *
	 * @return string
	 */
	public function getVersion(): string;

	/**
	 * Package author
	 *
	 * @return string
	 */
	public function getAuthor(): string;

	/**
	 * Package url link
	 *
	 * @return string
	 */
	public function getLink(): string;

	/**
	 * Package readme.md data text
	 *
	 * @return string
	 */
	public function getReadme(): string;

	/**
	 * Package license
	 *
	 * @return string
	 */
	public function getLicense(): string;
}