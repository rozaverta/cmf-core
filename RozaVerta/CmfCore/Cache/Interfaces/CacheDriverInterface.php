<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.08.2018
 * Time: 13:35
 */

namespace RozaVerta\CmfCore\Cache\Interfaces;

interface CacheDriverInterface
{
	public function load( int $life = 0 );

	public function has(): bool;

	public function set( string $value ): bool;

	public function export( $data ): bool;

	public function get();

	public function import();

	public function forget(): bool;
}