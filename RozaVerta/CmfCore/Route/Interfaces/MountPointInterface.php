<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.04.2019
 * Time: 10:16
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

use Countable;
use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;

interface MountPointInterface extends Countable, ModuleGetterInterface
{
	/**
	 * @return int
	 */
	public function getId(): int;

	/**
	 * @return string
	 */
	public function getPathName(): string;

	/**
	 * @return bool
	 */
	public function isHomePage(): bool;

	/**
	 * @return bool
	 */
	public function is404(): bool;

	/**
	 * @return bool
	 */
	public function isBasePath(): bool;

	/**
	 * @return string
	 */

	public function getPath(): string;

	/**
	 * @return bool
	 */
	public function isClose(): bool;

	/**
	 * @param int $number
	 * @return string|null
	 */
	public function getSegment( int $number = 0 ): ? string;

	/**
	 * @return array
	 */
	public function getSegments(): array;
}