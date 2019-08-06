<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 22.04.2019
 * Time: 10:16
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

use Countable;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;

/**
 * Interface MountPointInterface
 *
 * @package RozaVerta\CmfCore\Route\Interfaces
 */
interface MountPointInterface extends Countable, ModuleGetterInterface, Arrayable
{
	/**
	 * Get the mount point identifier.
	 *
	 * @return int
	 */
	public function getId(): int;

	/**
	 * @return string
	 */
	public function getPathName(): string;

	/**
	 * Point mounted as homepage.
	 *
	 * @return bool
	 */
	public function isHomePage(): bool;

	/**
	 * Point is mounted as a page not found.
	 *
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
	 * Get a path segment.
	 *
	 * @param int $number
	 *
	 * @return string|null
	 */
	public function getSegment( int $number = 0 ): ? string;

	/**
	 * Get the path as an array of segments.
	 *
	 * @return array
	 */
	public function getSegments(): array;
}