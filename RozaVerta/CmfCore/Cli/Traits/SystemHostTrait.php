<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 18:58
 */

namespace RozaVerta\CmfCore\Cli\Traits;

use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Host\HostManager;

trait SystemHostTrait
{
	use IOTrait;

	/**
	 * @return bool
	 */
	public function isHost(): bool
	{
		return HostManager::getInstance()->isDefined();
	}

	public function isInstall(): bool
	{
		return App::getInstance()->isInstall();
	}

	public function inInstallUpdateProgress(): bool
	{
		if( !$this->isHost() )
		{
			return false;
		}

		$status = App::getInstance()->system("status");
		return strpos($status, "-progress") > 0 || $status === "progress";
	}

	/**
	 * @return HostManager
	 */
	protected function getHost(): HostManager
	{
		if( $this->isHost() )
		{
			return HostManager::getInstance();
		}

		$io = $this->getIO();

		// provide host
		$host_name = $io->ask('Provide a hostname: ');
		$host_name = trim($host_name);
		if( ! strlen($host_name) )
		{
			return $this->getHost();
		}

		// exit ?
		if( $host_name === 'exit' && $io->confirm("Exit (y/n)? ") )
		{
			exit;
		}

		// reload
		$host = HostManager::getInstance();
		try {
			$reload = $host->reload($host_name);
		}
		catch( \InvalidArgumentException $e ) {
			$io->write("<error>Warning:</error> " . $e->getMessage());
			return $this->getHost();
		}

		// select host nof found, find else
		if( ! $reload )
		{
			$io->write("<error>Warning:</error> The '{$host_name}' host not found");
			return $this->getHost();
		}

		// redirect host ?
		if($host->isRedirect())
		{
			$io->write("<error>Warning:</error> The '{$host_name}' host is already used for redirection to the '" . $host->getRedirectUrl() . "' url, select another");
			return $this->getHost();
		}

		// select host, define constants
		$host->define();

		// check install or update process
		if( $this->inInstallUpdateProgress() )
		{
			throw new \InvalidArgumentException("Warning! The process of installing or updating the system was started, please wait");
		}

		return $host;
	}
}