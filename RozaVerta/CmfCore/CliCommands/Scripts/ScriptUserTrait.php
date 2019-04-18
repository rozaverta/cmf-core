<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.08.2018
 * Time: 22:03
 */

namespace RozaVerta\CmfCore\CliCommands\Scripts;

use RozaVerta\CmfCore\Cli\IO\InputOutputInterface;

trait ScriptUserTrait
{
	protected $user = null;

	abstract protected function getIO(): InputOutputInterface;

	protected function getScriptUser( bool $update = false ): string
	{
		if( $this->user && ! $update )
		{
			return $this->user;
		}

		$user = get_current_user();
		if( !$user )
		{
			$user = "www-data";
		}

		$io = $this->getIO();
		$accept = $io->confirm(
			($user === "root" ? "<error>Warning!</error> " : "") .
			"Name of the owner script user is <comment>{$user}</comment>, do you want change it (y/n)? "
		);

		if( $accept )
		{
			$user = $this->askScriptUser();
		}

		$this->user = $user;
		return $this->user;
	}

	private function askScriptUser(): string
	{
		$io = $this->getIO();
		$user = $io->ask("Enter username: ");
		$user = trim($user);
		if( ! strlen($user) || preg_match('/[^a-z0-9\-_]/i', $user) )
		{
			$io->write("<error>Wrong:</error> Invalid user name");
			return $this->askScriptUser();
		}

		return strtolower($user);
	}
}