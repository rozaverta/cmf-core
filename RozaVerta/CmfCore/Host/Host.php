<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 20.03.2019
 * Time: 14:54
 */

namespace RozaVerta\CmfCore\Host;

use RozaVerta\CmfCore\Helper\Str;
use RozaVerta\CmfCore\Host\Exceptions\InvalidHostNameException;
use RozaVerta\CmfCore\Host\Interfaces\HostInterface;

class Host implements HostInterface
{
	/**
	 * @var string
	 */
	private $hostname;

	private $ssl = false;

	private $port = 80;

	public function __construct(string $host)
	{
		$filterHost = self::filter($host, true, $match);
		if( ! $filterHost )
		{
			throw new InvalidHostNameException("Invalid host name {$host}");
		}

		$this->hostname = $filterHost;
		$this->ssl  = $match["ssl"] ?? false;
		$this->port = $match["port"] ?? 80;
	}

	/**
	 * @return string
	 */
	public function getHostname(): string
	{
		return $this->hostname;
	}

	/**
	 * @return int
	 */
	public function getPort(): int
	{
		return $this->port;
	}

	/**
	 * @return bool
	 */
	public function isSsl(): bool
	{
		return $this->ssl;
	}

	public static function filter( string $host, bool $filterStrip = false, & $match = null )
	{
		$test = Str::lower(trim($host));

		$match = [
			'ssl'  => false,
			'port' => 80
		];

		// remove ^ http:// or https://
		if( preg_match('|^(https?)://(.*?)$|', $test, $m) )
		{
			$match['ssl'] = $m[1] === "https";
			$test = $m[2];
		}

		// remove port
		if( preg_match('|^(.*?):(\d{1,4})\/?$|', $test, $m) )
		{
			$match['port'] = (int) $m[2];
			$test = $m[1];
		}

		if( !strlen($test) )
		{
			return false;
		}

		$test = filter_var($test, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
		if( !$test )
		{
			return false;
		}

		return $filterStrip ? $test : $host;
	}

	public function toArray(): array
	{
		$row = [
			"name" => $this->getHostname(),
			"ssl"  => $this->isSsl(),
			"port" => $this->getPort()
		];

		return $row;
	}
}