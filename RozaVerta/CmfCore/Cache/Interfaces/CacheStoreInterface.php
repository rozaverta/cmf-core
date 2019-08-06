<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 17.08.2018
 * Time: 20:15
 */

namespace RozaVerta\CmfCore\Cache\Interfaces;

interface CacheStoreInterface
{
	/**
	 * The store name
	 *
	 * @return string
	 */
	public function getName(): string;

	/**
	 * The store driver name
	 *
	 * @return string
	 */
	public function getDriver(): string;

	/**
	 * The default ttl for cache
	 *
	 * @return int
	 */
	public function getLife(): int;

	public function createFactory( string $name, string $prefix = "", array $properties = [], int $life = null ): CacheDriverInterface;

	public function flush( string $prefix = null ): bool;

	public function info(): array;

	public function stats(): array;
}