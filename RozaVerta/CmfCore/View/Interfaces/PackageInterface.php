<?php

namespace RozaVerta\CmfCore\View\Interfaces;

use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;

interface PackageInterface extends ModuleGetterInterface, Arrayable
{
	/**
	 * PackageHelper ID
	 *
	 * @return int
	 */
	public function getId(): int;

	/**
	 * PackageHelper name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * PackageHelper description
	 *
	 * @return string
	 */
	public function getDescription(): string;

	/**
	 * PackageHelper version, default 1.0
	 *
	 * @return string
	 */
	public function getVersion(): string;

	/**
	 * PackageHelper author
	 *
	 * @return string
	 */
	public function getAuthor(): string;

	/**
	 * PackageHelper url link
	 *
	 * @return string
	 */
	public function getLink(): string;

	/**
	 * PackageHelper readme.md data text
	 *
	 * @return string
	 */
	public function getReadme(): string;

	/**
	 * PackageHelper license
	 *
	 * @return string
	 */
	public function getLicense(): string;
}