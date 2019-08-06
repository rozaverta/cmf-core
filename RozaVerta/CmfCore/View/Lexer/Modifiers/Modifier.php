<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:55
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\Helper\Str;
use RozaVerta\CmfCore\View\Exceptions\ModifierNotFoundException;
use RozaVerta\CmfCore\View\Interfaces\ModifierInterface;

abstract class Modifier implements ModifierInterface
{
	/**
	 * @var ModifierInterface[]
	 */
	static private $loaded = [];

	static private $modifiers = [
		'dateTime' => DateFormat::class,
		'debug' => Debug::class,
		'entity' => Entity::class,
		'escape' => Escape::class,
		'if' => IfOperator::class,
		'length' => Length::class,
		'lower' => Lower::class,
		'replace' => Replace::class,
		'stripTags' => StripTags::class,
		'substr' => Substr::class,
		'title' => Title::class,
		'upper' => Upper::class,
	];

	static public function checkFlag( array & $attributes ): bool
	{
		$first = count($attributes) ? current($attributes) : null;

		if(is_bool($first))
		{
			if($first === false)
			{
				return false;
			}
			else
			{
				array_shift($attributes);
			}
		}

		return true;
	}

	static public function modifier(string $name): ModifierInterface
	{
		if( ! isset(self::$loaded[$name]) )
		{
			if( ! isset(self::$modifiers[$name]) )
			{
				throw new ModifierNotFoundException("The '{$name}' lexer modifier not found");
			}

			$modifier = new self::$modifiers[$name];
			if( ! $modifier instanceof ModifierInterface )
			{
				// todo error
			}

			self::$loaded[$name] = $modifier;
		}

		return self::$loaded[$name];
	}

	static public function exists(string $name): bool
	{
		return isset(self::$modifiers[$name]);
	}

	static public function register(string $name, string $modifier)
	{
		$name = Str::camel($name);
		if( self::exists($name) )
		{
			throw new \InvalidArgumentException("Modifier '{$name}' already exists");
		}

		self::$modifiers[$name] = $modifier;
	}

	static public function override(string $name, string $modifier)
	{
		$name = Str::camel($name);
		self::$modifiers[$name] = $modifier;
		if( isset(self::$loaded[$name]) )
		{
			unset(self::$loaded[$name]);
		}
	}
}