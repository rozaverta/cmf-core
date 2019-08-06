<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.03.2019
 * Time: 21:23
 */

namespace RozaVerta\CmfCore\View;

use RozaVerta\CmfCore\Log\LogManager;
use RozaVerta\CmfCore\View\Interfaces\LexerInterface;
use RozaVerta\CmfCore\View\Interfaces\PluginDynamicInterface;
use RozaVerta\CmfCore\View\Interfaces\PluginStaticInterface;
use RozaVerta\CmfCore\View\Lexer\Attr;
use RozaVerta\CmfCore\View\Lexer\Modifiers\Modifier;
use RozaVerta\CmfCore\View\Lexer\Modifiers\Nil;
use Traversable;

class Lexer implements LexerInterface
{
	protected $items = [];

	protected $itemsCache = [];

	protected $parentLexer = null;

	/**
	 * @var array | PluginStaticInterface[]
	 */
	protected $plugins = [];

	private static $globalDepth = 0;

	public function __construct( array $items, ? Lexer $lexer = null )
	{
		$this->items = $items;
		$this->parentLexer = $lexer;
	}

	public function has( string $name ): bool
	{
		if( strpos($name, ".") !== false )
		{
			$path = explode(".", $name);
			$len = count($path);
			$current = 0;

			if($path[0] === "parent")
			{
				if( $this->parentLexer !== null )
				{
					return $this->parentLexer->has( substr($name, 7) );
				}
				else
				{
					return false;
				}
			}
			else
			{
				$value = & $this->items;
			}

			for(; $current < $len; $current ++)
			{
				$key = $path[$current];

				if(is_array($value))
				{
					if(array_key_exists($key, $value))
					{
						$value = & $value[$key];
					}
					else
					{
						return false;
					}
				}

				else if(isset($value[$key]))
				{
					$value = $value[$key];
				}

				else
				{
					return false;
				}
			}

			return true;
		}

		if( $name === "parent" )
		{
			return $this->parentLexer !== null;
		}

		if( array_key_exists($name, $this->items) )
		{
			return true;
		}

		return false;
	}

	public function set( $name, $value = null )
	{
		if( is_array($name) )
		{
			foreach($name as $n => $value)
			{
				$this->set($n, $value);
			}
		}
		else
		{
			$name = (string) $name;
			if( strpos($name, ".") !== false )
			{
				$this->writePath(explode(".", $name), $value);
			}
			else if($name !== "parent")
			{
				$this->items[$name] = $value;
			}
		}

		return $this;
	}

	public function forgot( $name )
	{
		$items = is_array($name) ? $name : func_get_args();
		foreach($items as $key)
		{
			unset($this->items[$key]);
		}
		return $this;
	}

	/**
	 * Get all data
	 *
	 * @param bool $parse
	 * @param array $ignore
	 * @return array
	 */
	public function all( bool $parse = false, array $ignore = [] ): array
	{
		if( count($ignore) )
		{
			$items = [];
			foreach( $this->items as $name => $value)
			{
				if( ! in_array($name, $ignore, true) )
				{
					$items[$name] = $parse ? $this->parse($value) : $value;
				}
			}
			return $items;
		}
		else
		{
			return $parse ? $this->parse($this->items) : $this->items;
		}
	}

	/**
	 * compile lexer
	 * save result to cache
	 *
	 * @param string $name
	 * @param null $default
	 * @return mixed
	 */
	public function get(string $name, $default = null)
	{
		if( isset($this->itemsCache[$name]) )
		{
			return $this->itemsCache[$name];
		}

		if( strpos($name, ".") !== false )
		{
			$value = $this->readPath(explode(".", $name), $default, $isDefault);
			if( $isDefault )
			{
				return $this->parse($value);
			}
		}
		else if( $name === "parent" )
		{
			$value = $this->parentLexer === null ? $default : $this->parentLexer->items;
		}
		else if( isset($this->items[$name]) )
		{
			$value = $this->items[$name];
		}
		else
		{
			return $this->parse($default);
		}

		$value = $this->parse($value);
		$this->itemsCache[$name] = $value;
		return $value;
	}

	/**
	 * compile lexer without cache
	 *
	 * @param string $name
	 * @param null $default
	 * @return mixed
	 */
	public function lexer(string $name, $default = null)
	{
		if( strpos($name, ".") !== false )
		{
			$value = $this->readPath(explode(".", $name), $default);
		}
		else if( $name === "parent" )
		{
			$value = $this->parentLexer === null ? $default : $this->parentLexer->items;
		}
		else if( isset($this->items[$name]) )
		{
			$value = $this->items[$name];
		}
		else
		{
			$value = $default;
		}

		return $this->parse($value);
	}

	/**
	 * return raw result
	 *
	 * @param string $name
	 * @param null $default
	 * @return mixed
	 */
	public function item( string $name, $default = null )
	{
		if( strpos($name, ".") !== false )
		{
			return $this->readPath(explode(".", $name), $default);
		}
		else if( $name === "parent" )
		{
			return $this->parentLexer === null ? $default : $this->parentLexer->items;
		}
		else if( isset($this->items[$name]) )
		{
			return $this->items[$name];
		}
		else
		{
			return $default;
		}
	}

	/**
	 * compile lexer text
	 *
	 * @param mixed $value
	 * @return mixed
	 */
	public function parse( $value )
	{
		if( is_array($value) || $value instanceof Traversable )
		{
			return $this->applyArray($value);
		}
		else if( is_string($value) )
		{
			return $this->apply($value);
		}
		else
		{
			return $value;
		}
	}

	/**
	 * select value if not empty
	 *
	 * @param $variant
	 * @return mixed
	 */
	public function choice($variant)
	{
		$remap = is_array($variant) ? $variant : func_get_args();

		foreach($remap as $name)
		{
			$value = $this->get( (string) $name, null );
			if( ! empty($value) || $value === "0" )
			{
				return $value;
			}
		}

		return null;
	}

	public function plugin(string $name, array $data = []): string
	{
		if( $this->parentLexer )
		{
			return $this->parentLexer->plugin($name, $data);
		}

		if( !Plugin::exists($name) )
		{
			return "[ERROR: the {$name} plugin not loaded]";
		}

		if( isset($this->plugins[$name]) )
		{
			return $this->plugins[$name]->render($data);
		}

		$plugin = Plugin::plugin($name, $this);

		if($plugin instanceof PluginStaticInterface)
		{
			$this->plugins[$name] = $plugin;
			return $plugin->render($data);
		}

		if($plugin instanceof PluginDynamicInterface)
		{
			return $this->pluginDynamic($name, $plugin, $data);
		}

		return $plugin->render();
	}

	protected function pluginDynamic(string $name, PluginDynamicInterface $plugin, array $data): string
	{
		return $plugin->ready($data)->render();
	}

	protected function writePath( array $path, $value )
	{
		$len = count($path);

		if($path[0] === "parent")
		{
			return;
		}

		$data = & $this->items;

		for($current = 0; $current < $len; $current ++)
		{
			$item = $path[$current];
			$last = $current + 1 === $len;

			if( $last )
			{
				$data[$item] = $value;
			}
			else
			{
				if( ! isset($data[$item]) || ! is_array($data[$item]) )
				{
					$data[$item] = [];
				}

				$data = & $data[$item];
			}
		}
	}

	protected function readPath( array $path, $default, & $isDefault = false)
	{
		$len = count($path);
		$current = 0;

		if($path[0] === "parent")
		{
			$current = 1;
			if( $this->parentLexer !== null )
			{
				$value = & $this->parentLexer->items;
			}
			else
			{
				$value = [];
			}
		}
		else
		{
			$value = & $this->items;
		}

		for(; $current < $len; $current ++)
		{
			$item = $path[$current];
			if( !isset($value[$item]) )
			{
				$isDefault = true;
				return $default;
			}

			$value = & $value[$item];
		}

		return $value;
	}

	protected function applyArray(iterable $iterator, $depth = 0)
	{
		if($depth > 10)
		{
			return [];
		}

		foreach($iterator as & $value)
		{
			if( is_array($value) || $value instanceof Traversable )
			{
				$value = $this->applyArray($value, $depth + 1);
			}
			else if( is_string($value) )
			{
				$value = $this->apply($value);
			}
		}

		return $iterator;
	}

	protected function apply(string $text, $depth = 0, $parse = []): string
	{
		if( strpos($text, '{{') === false )
		{
			return $text;
		}

		$parser = new Lexer\Parser($text);
		if( ! $parser->parse() )
		{
			// todo add debug text
			return $text;
		}

		$increment = $this->parentLexer !== null;
		if($increment)
		{
			self::$globalDepth ++;
		}

		// todo parse remap ...
		$parts = $parser->parts();
		$text  = "";

		/** @var Lexer\Node $node */
		foreach($parts as $node)
		{
			if($node->type === Lexer\Node::T_TEXT)
			{
				$text .= $node->text;
			}
			else
			{
				$text .= $this->replace($node);
			}
		}

		print_r($parts);

		if($increment && self::$globalDepth > 0)
		{
			self::$globalDepth --;
		}

		return $text;
	}

	protected function attr(Lexer\Attr $at, int $depth = 0)
	{
		$val = $at->value;

		if($at->type === Lexer\Attr::T_SCALAR)
		{
			if( is_string($val) && strpos($val, '{{') !== false )
			{
				// todo
			}
			return $val;
		}

		if($at->type === Lexer\Attr::T_ARRAY)
		{
			$map = [];

			foreach((array) $at->value as $val)
			{
				if($val instanceof Lexer\Node)
				{
					// $map[] = ''; TODO
				}
				else if(is_string($val) && strpos($val, '{{') !== false)
				{
					$map[] = $val; // todo $this->apply($val ... )
				}
				else
				{
					$map[] = $val;
				}
			}

			return $map;
		}

		if($at->type === Lexer\Attr::T_NODE && $val instanceof Lexer\Node)
		{
			// todo
		}

		return null;
	}

	protected function replace(Lexer\Node $node)
	{
		$raw = isset($node->attr['raw']) ? $this->attr($node->attr['raw'], 0) : false;
		if($raw)
		{
			return Lexer\Node::toText($node, $raw);
		}

		$depth = 0;
		$literal = false;

		if(isset($node->attr['depth']))
		{
			$depth = (int) $this->attr($node->attr['depth'], 0);
			if($depth > 20)
			{
				$depth = 20;
			}
		}

		if(isset($node->attr['literal']))
		{
			$literal = (bool) $this->attr($node->attr['literal'], $depth);
		}

		if($node->type === Lexer\Node::T_VAR)
		{
			$value = $this->get($node->name);

			if(isset($node->attr["type"]))
			{
				$type = $this->attr($node->attr["type"], $depth);
				switch($type)
				{
					case "string": $value = (string) $value; break;
					case "int":    $value = (int) $value; break;
					case "float":  $value = (float) $value; break;
					case "array":
						if( !is_array($value) )
						{
							$value = $value instanceof \Traversable ? iterator_to_array($value) : [$value];
						}
						break;
				}
			}

			if(is_string($value))
			{
				$trim = isset($node->attr["trim"]) ? $this->attr($node->attr["trim"], $depth) : false;
				if($trim)
				{
					$value = $trim === true ? trim($value) : trim($value, $trim);
				}
			}

			if(empty($value) && $value !== "0" && isset($node->attr["default"]))
			{
				$value = $this->attr($node->attr["default"], $depth);
			}

			$value = $this->varMod($value, $node->attr);
		}

		else
		{
			$params = [];
			foreach(array_keys($node->attr) as $name)
			{
				if($name === 'raw' || $name === 'literal' || $name === 'depth')
				{
					continue;
				}

				$params[$name] = $this->attr($node->attr[$name], $depth);
			}

			$value = $this->plugin($node->name, $params);
		}

		if($literal || $depth === 0)
		{
			return $value;
		}

		if(is_array($value))
		{
			// todo
		}
		else if(is_string($value) && strpos($value, '{{') !== false)
		{
			// todo
		}

		return $value;
	}

	/**
	 * @param $value
	 * @param Attr[] $attributes
	 * @return mixed
	 */
	protected function varMod($value, array $attributes)
	{
		foreach($attributes as $attr)
		{
			$name = $attr->name;
			if( ! Modifier::exists($name) )
			{
				continue;
			}

			$attributes = $this->attr($attr);
			if( !is_array($attributes) )
			{
				$attributes = [$attributes];
			}

			try {
				$value = Modifier::modifier( $name )->format( $value, $attributes );
			}
			catch( \Exception $e ) {
				Modifier::register($name, Nil::class);
				LogManager::getInstance()->addError($e->getMessage()); // todo change Exception valid mode
			}
		}

		return $value;
	}
}