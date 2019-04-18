<?php

/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 25.09.2017
 * Time: 22:59
 */

namespace RozaVerta\CmfCore\Language;

use RozaVerta\CmfCore\Log\LogManager;
use RozaVerta\CmfCore\Support\Text;

abstract class Language
{
	protected $lines = [];

	protected $language;

	public function __construct( $language )
	{
		$this->language = $language;
		$this->load("default");
	}

	abstract protected function loadPackage( string $package_name );

	public function packages()
	{
		return array_keys($this->lines);
	}

	public function loadIs( string $package_name ): bool
	{
		return array_key_exists($package_name, $this->lines);
	}

	public function load( string $package_name )
	{
		if( $this->loadIs($package_name) )
		{
			return true;
		}

		$lines = $this->loadPackage($package_name);
		if( !is_array($lines) )
		{
			LogManager::getInstance()->line( Text::text("Cannot load language package %s", $package_name) );
			return false;
		}

		$this->lines[$package_name] = $lines;
		return true;
	}

	public function itemIs( string $name, string $context = "default" ): bool
	{
		return isset($this->lines[$context][$name]);
	}

	public function item( string $name, string $context = "default", string $default = "" )
	{
		return $this->lines[$context][$name] ?? $default;
	}
}