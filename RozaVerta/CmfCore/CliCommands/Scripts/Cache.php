<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.08.2018
 * Time: 14:10
 */

namespace RozaVerta\CmfCore\CliCommands\Scripts;

use RozaVerta\CmfCore\Cache\Properties\Property;
use RozaVerta\CmfCore\Cache\Properties\PropertyMemory;
use RozaVerta\CmfCore\Cli\IO\Option;

/**
 * Class Cache
 *
 * @package RozaVerta\CmfCore\CliCommands\Scripts
 */
class Cache extends AbstractScript
{
	protected function init()
	{
		$this->getHost();
	}

	public function menu()
	{
		$variant = $this
			->getIO()
			->askOptions([
				new Option("clear all cache", 1),
				new Option("clear prefix cache", 2),
				new Option("show info", 3),
				new Option("stats", 4),
				new Option("exit")
			]);

		switch($variant)
		{
			case 1: $this->flush(); break;
			case 2: $this->flushAskPrefix(); break;
			case 3: $this->info(); break;
			case 4: $this->stats(); break;
		}
	}

	public function flush( string $prefix = null )
	{
		try {
			$clean = \RozaVerta\CmfCore\Cache\Cache
				::store()
				->flush($prefix);

			if( !$clean )
			{
				throw new \Exception("Cannot flush cache, system error");
			}

			$this
				->getIO()
				->write("<info>Success!</info> The cache data was successfully deleted");
		}
		catch(\Exception $e)
		{
			$this
				->getIO()
				->write("<error>Wrong!</error> " . $e->getMessage());
		}
	}

	public function info()
	{
		$info = \RozaVerta\CmfCore\Cache\Cache::store()->info();
		$rows = [];

		/** @var Property $prop */
		foreach($info as $prop)
		{
			$rows[] = [
				$prop->getName(), $this->getTypeString($prop->getValue())
			];
		}

		$this
			->getIO()
			->table($rows, ["Name", "Driver"]);
	}

	public function stats()
	{
		$stats = \RozaVerta\CmfCore\Cache\Cache::store()->stats();

		$rows = [];
		$prev = null;

		/** @var Property $prop */
		foreach($stats as $prop)
		{
			$current = get_class($prop);

			if( $prev && $current !== $prev )
			{
				$rows[] = null;
			}

			$prev = $current;

			if( $prop instanceof PropertyMemory )
			{
				$key = 'NS <info>' . $prop->getName() . '</info>';
				$val = $this->getSizeUnits($prop->getValue());
				if($prop->getCount() > 1)
				{
					$val .= ' <comment>[' . $prop->getCount() . ']</comment>';
				}
			}
			else
			{
				$key = $prop->getName();
				$val = $this->getTypeString($prop->getValue());
			}

			$rows[] = [
				$key, $val
			];
		}

		$this
			->getIO()
			->table($rows, ["Name", "Driver"]);
	}

	private function flushAskPrefix()
	{
		$prefix = trim( $this->getIO()->ask("Enter cache prefix: ") );
		if( !strlen($prefix) )
		{
			$this->flushAskPrefix();
		}
		else
		{
			$this->flush($prefix);
		}
	}

	private function getTypeString( $value, $depth = 0 ): string
	{
		if( is_null($value) )
		{
			return '<info>NULL</info>';
		}

		if( is_bool($value) )
		{
			return $value ? '<info>YES</info>' : '<error>NO</error>';
		}

		if( is_numeric($value) )
		{
			return '<comment>' . $value . '</comment>';
		}

		if( is_array($value) )
		{
			if( $depth > 0 )
			{
				return '[<info>' . count($value) . '</info>]';
			}

			return implode(", ", array_map(function($val) {
				return $this->getTypeString($val, 1);
			}, $value));
		}

		if( is_object($value) && ! method_exists($value, '__toString') )
		{
			return get_class($value);
		}

		return (string) $value;
	}

	private function getSizeUnits(int $size): string
	{
		static $units = [ ' bytes', ' Kb', ' Mb', ' Gb', ' Tb', ' Pb', ' Eb', ' Zb', ' Yb' ];
		if( $size < 1 )
		{
			return '0 bytes';
		}
		if( $size < 1024 )
		{
			return $size . ( $size === 1 ? ' byte' : ' bytes');
		}

		$power = (int) floor( log($size, 1024) );
		return number_format($size / pow(1024, $power), 2, '.', ',') . $units[$power];
	}
}