<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.07.2018
 * Time: 23:21
 */

namespace RozaVerta\CmfCore\Cli\PhpLexer;

use ArrayIterator;
use Countable;
use ReflectionClass;
use RozaVerta\CmfCore\Support\Collection;
use RozaVerta\CmfCore\Interfaces\Arrayable;
use IteratorAggregate;

class CommentClass implements Arrayable, IteratorAggregate, Countable
{
	private $pos = 0;

	private $name = null;

	private $lines = [];

	/**
	 * @var CommentBlock[]
	 */
	private $items = [];

	private $length = 0;

	private $group = [];

	private $group_text = [[], 0, 0];

	/**
	 * @var ReflectionClass
	 */
	protected $reflection;

	public function __construct( $class_name )
	{
		$this->reflection = new ReflectionClass($class_name);

		$comment = trim($this->reflection->getDocComment());

		if(substr($comment, 0, 3) === "/**" && $comment !== '/**/')
		{
			$comment = str_replace(["\r\n", "\r"], "\n", $comment);
			$comment = preg_replace('/[ \t]+/', " ", $comment);
			$comment = ltrim( substr($comment, 1), "*" );

			if( strrpos($comment, '*/') === strlen($comment) - 2 )
			{
				$comment = rtrim($comment, ' */');
			}

			$all = array_map("trim", explode("\n", $comment) );

			while( $this->loadNext($all) )
			{
				if( $this->name )
				{
					if( !isset($this->group[$this->name]) )
					{
						$this->group[$this->name] = [ [], 0, 0 ];
					}

					$this->group[$this->name][0][] = $this->length;
					$this->group[$this->name][2] ++;
				}
				else
				{
					$this->group_text[0][] = $this->length;
					$this->group_text[2] ++;
				}

				$this->items[$this->length++] = new CommentBlock($this->name, $this->lines);
			}
		}
	}

	/**
	 * @param $name
	 * @return CommentBlock|null
	 */
	public function getParam($name): ?CommentBlock
	{
		return isset($this->group[$name]) ? $this->getFrom($this->group[$name]) : null;
	}

	public function getParams(string $name): Collection
	{
		static $null = [null, null, 0];
		return $this->getFromCollection( isset($this->group_text[$name]) ? $this->group_text[$name] : $null );
	}

	public function resetParam(string $name)
	{
		if(isset($this->group[$name]))
		{
			$this->group[$name][1] = 0;
		}

		return $this;
	}

	/**
	 * @return CommentBlock|null
	 */
	public function getTextNode(): ?CommentBlock
	{
		return $this->getFrom($this->group_text);
	}

	/**
	 * @return Collection
	 */
	public function getTextNodes(): Collection
	{
		return $this->getFromCollection($this->group);
	}

	public function resetText()
	{
		$this->group_text[1] = 0;
		return $this;
	}

	/**
	 * @param string $name
	 * @return bool
	 */
	public function hasParam(string $name): bool
	{
		return isset($this->group[$name]);
	}

	/**
	 * @return bool
	 */
	public function hasAnnotation(): bool
	{
		return $this->length > 0 && $this->items[0]->isTextNode();
	}

	/**
	 * @return bool
	 */
	public function hasDescription(): bool
	{
		return $this->hasParam('description') || $this->hasAnnotation();
	}

	/**
	 * @return CommentBlock|null
	 */
	public function getAnnotation(): ?CommentBlock
	{
		return $this->hasAnnotation() ? $this->items[0] : null;
	}

	/**
	 * @return CommentBlock|null
	 */
	public function getDescription()
	{
		if($this->hasParam('description'))
		{
			$this->resetParam('description');
			return $this->getParam('description');
		}
		else if( $this->hasAnnotation() )
		{
			return $this->getAnnotation();
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return array_map(static function(CommentBlock $block) {
			if($block->isTextNode())
			{
				return [
					"type"  => "text",
					"lines" => $block->getLines()
				];
			}
			else
			{
				return [
					"type"  => "block",
					"name"  => $block->getName(),
					"lines" => $block->getLines()
				];
			}
		}, $this->items);
	}

	/**
	 * Retrieve an external iterator
	 * @link http://php.net/manual/en/iteratoraggregate.getiterator.php
	 * @return \Traversable An instance of an object implementing <b>Iterator</b> or
	 * <b>Traversable</b>
	 * @since 5.0.0
	 */
	public function getIterator()
	{
		return new ArrayIterator($this->items);
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return $this->length;
	}

	/**
	 * @return ReflectionClass
	 */
	public function getReflection(): ReflectionClass
	{
		return $this->reflection;
	}

	private function loadNext( & $all )
	{
		$this->name = null;
		$this->lines = [];

		$n = -1;
		$length = count($all);

		while( $this->pos < $length )
		{
			$key = $all[$this->pos++];

			$merge = strlen($key) && $key[0] === "*";
			if( $merge )
			{
				$key = ltrim($key, "*");
				$key = ltrim($key);
			}

			if(strlen($key) < 1)
			{
				if( $n > -1 )
				{
					break;
				}
				else
				{
					continue;
				}
			}

			if( $key[0] === "@" )
			{
				if( $n > -1 )
				{
					$this->pos --;
					break;
				}

				$key = $this->setName($key);
				if( strlen($key) )
				{
					$this->lines[++$n] = $key;
				}
				else if( $this->name )
				{
					$n = 0;
				}
			}
			else if( $merge || $n < 0 )
			{
				$this->lines[++$n] = $key;
			}
			else
			{
				$this->lines[$n] .= " " . $key;
			}
		}

		return $n > -1;
	}

	private function setName($key)
	{
		if(preg_match('/^@+(.*?)(?:\s+|$)/', $key, $m))
		{
			$name = trim($m[1]);
			if(strlen($name))
			{
				$this->name = $name;
			}

			$value = substr($key, strlen($m[0]));
			return trim($value);
		}
		else
		{
			return ltrim($key, "@");
		}
	}

	/**
	 * @param $point
	 * @return CommentBlock|null
	 */
	private function getFrom( & $point ): ?CommentBlock
	{
		if( $point[1] >= $point[2] )
		{
			return null;
		}

		return $this->items[ $point[0][$point[1]++] ];
	}

	private function getFromCollection( & $point ): Collection
	{
		$result = new Collection();

		if( $point[2] > 0 )
		{
			foreach($point[0] as $index)
			{
				$result[] = $this->items[$index];
			}
		}

		return $result;
	}
}