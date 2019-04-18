<?php
/**
 * Created by IntelliJ IDEA.
 * User: gosha
 * Date: 16.02.2017
 * Time: 20:26
 */

namespace RozaVerta\CmfCore\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use RozaVerta\CmfCore\Helper\Callback;
use RozaVerta\CmfCore\Helper\Path;
use Traversable;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use RozaVerta\CmfCore\Traits\GetTrait;
use RozaVerta\CmfCore\Traits\SetTrait;
use RozaVerta\CmfCore\Traits\ComparatorTrait;

/**
 * Class Prop
 *
 * @package RozaVerta\CmfCore\Support
 */
class Prop implements ArrayAccess, Countable, Arrayable, IteratorAggregate
{
	use GetTrait;
	use SetTrait;
	use ComparatorTrait;

	protected $items = [];

	public function __construct( $prop = [], $indexKey = true )
	{
		if( is_string($prop) )
		{
			$prop = self::file($prop);
		}
		else if( $prop instanceof Arrayable )
		{
			$prop = $prop->toArray();
		}
		else if($prop instanceof Traversable)
		{
			$prop = iterator_to_array($prop);
		}

		if( is_array($prop) )
		{
			if( $indexKey )
			{
				foreach( $prop as $key => $value )
				{
					if( is_int($key) && is_string($value) )
					{
						$this->items[$value] = true;
					}
					else
					{
						$this->items[$key] = $value;
					}
				}
			}
			else
			{
				$this->items = $prop;
			}
		}
	}

	/**
	 * @param array $an_array
	 * @return static
	 */
	public static function __set_state($an_array)
	{
		return new static($an_array["items"] ?? []);
	}

	/**
	 * Get array data from file
	 *
	 * @param $name
	 * @param bool $exists
	 * @return array
	 */
	public static function file( $name, & $exists = null )
	{
		$file = Path::config( $name . '.php' );
		if( file_exists( $file ) )
		{
			$data = Callback::tap(static function($file) { return require $file; }, $file);
			$exists = true;
		}
		else
		{
			$exists = false;
		}

		if( ! isset( $data ) || ! is_array( $data ) )
		{
			$data = [];
		}

		return $data;
	}

	/**
	 * Get new property group
	 *
	 * @param $name
	 * @return Prop
	 */
	public function group( $name )
	{
		$name = rtrim($name, '.');
		$pref = $name . '.';
		$len = strlen($pref);
		$data = [];

		foreach( array_keys($this->items) as $key )
		{
			if( $key === $name )
			{
				$data['.'] = $this->items[$key];
			}
			else if( strlen($key) > $len && substr($key, 0, $len) === $pref )
			{
				$data[substr($key, $len)] = $this->items[$key];
			}
		}

		return new self($data);
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 *
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return count($this->items);
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	static private $props = [];

	static public function prop( string $name ): Prop
	{
		if( ! isset(self::$props[$name]) )
		{
			self::$props[$name] = new Prop($name);
		}
		return self::$props[$name];
	}
}