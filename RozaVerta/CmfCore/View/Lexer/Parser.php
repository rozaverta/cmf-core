<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.03.2019
 * Time: 21:37
 */

namespace RozaVerta\CmfCore\View\Lexer;

class Parser
{
	const ATTR_NAME  = 1;
	const ATTR_VALUE = 2;
	const ATTR_ARRAY = 3;

	private $text;

	private $length;

	private $last;

	private $parts = [];

	public function __construct(string $text)
	{
		$this->text = $text;
		$this->length = strlen($text);
		$this->last = $this->length > 0 ? $this->length - 1 : 0;
	}

	public function parse()
	{
		$this->parts = [];

		$start = strpos($this->text, '{{');
		if( $start === false )
		{
			return false;
		}

		$pos = 0;
		$found = false;
		$prev = -1;

		do {
			$delta = $start - $pos;

			if( $delta > 0 )
			{
				$node = new Node(Node::T_TEXT, substr($this->text, $pos, $delta));
				$this->parts[++$prev] = $node;
			}

			$node = $this->read($start);
			$plain = $node->type === Node::T_TEXT;
			$pos = $start + strlen($node->text);

			if($plain && $prev > -1 && $this->parts[$prev]->type === Node::T_TEXT)
			{
				$this->parts[$prev]->text .= $node->text;
			}
			else
			{
				$this->parts[++$prev] = $node;
				if(! $plain)
				{
					$found = true;
				}
			}

			$start = strpos($this->text, "{{", $pos);

			if( $start === false && $pos + 1 < $this->length )
			{
				if($plain && $prev > -1 && $this->parts[$prev]->type === Node::T_TEXT)
				{
					$this->parts[$prev]->text .= substr($this->text, $pos);
				}
				else
				{
					$node = new Node(Node::T_TEXT, substr($this->text, $pos));
					$this->parts[] = $node;
				}
			}
		}
		while($start !== false);

		return $found;
	}

	public function parts()
	{
		return $this->parts;
	}

	private function read(int $start): Node
	{
		$chr = $this->fill($start + 2);
		if($chr === false)
		{
			return $this->readAbort($start, $start + 2);
		}

		$val = $this->text[$chr];
		if($val === '$')
		{
			return $this->readVar($start, $chr + 1);
		}
		else if(ctype_alpha($val))
		{
			return $this->readTag($start, $chr);
		}

		return $this->readAbort($start, $chr);
	}

	private function readVar(int $start, int $open): Node
	{
		$name = "";
		$lastDot = false;

		while($open + 1 < $this->length)
		{
			$cur = $this->text[$open];

			if(ctype_alnum($cur))
			{
				$name .= $cur;
				$lastDot = false;
			}
			else if( ! $lastDot && $cur === "." )
			{
				$name .= $cur;
				$lastDot = true;
			}
			else
			{
				break;
			}

			$open ++;
		}

		if( !strlen($name) )
		{
			return $this->readAbort($start, $open);
		}

		return $this->readAttr(new Node(Node::T_VAR, "", $name), $start, $open);
	}

	private function readTag(int $start, int $open): Node
	{
		$name = $this->readName($open, $offset);
		if($name === false)
		{
			return $this->readAbort($start, $open);
		}
		return $this->readAttr(new Node(Node::T_TAG, "", $name), $start, $offset);
	}

	private function readName(int $start, & $offset)
	{
		$name = $this->text[$start++];
		if( !ctype_alpha($name) )
		{
			return false;
		}

		$back = false;
		while($start + 1 < $this->length)
		{
			$cur = $this->text[$start];

			if(ctype_alnum($cur))
			{
				$name .= $cur;
				$back = false;
			}
			else if( ! $back && $cur === "_" )
			{
				$name .= " ";
				$back = true;
			}
			else
			{
				break;
			}

			$start ++;
		}

		if( $back )
		{
			return false;
		}

		// format name ...

		$name = str_replace(' ', '', ucwords($name));
		$name = lcfirst($name);

		$offset = $start;

		return $name;
	}

	private function readAttr(Node $node, int $start, int $open): Node
	{
		$next = $this->fill($open);
		if( $next === false )
		{
			return $this->readAbort($start, $open, $node);
		}

		$type = Parser::ATTR_NAME;
		$attr = new Attr();
		$prev = $open;
		$open = $next;

		while($open !== false && $open + 1 < $this->length)
		{
			$one = $this->text[$open];
			$prev = $open;

			// ATTR_NAME

			if($type === Parser::ATTR_NAME)
			{
				if(ctype_alpha($one))
				{
					$name = $this->readName($open, $offset);
					if($name === false)
					{
						break;
					}

					$open = $this->fill($offset);
					if($open === false)
					{
						break;
					}

					$attr->name = $name;
					$node->attr[$name] = $attr;

					$one = $this->text[$open];
					if($one === "=")
					{
						$type = Parser::ATTR_VALUE;
						$open = $this->fill($open + 1);
					}
					else
					{
						$attr->value = true;

						if($one === "}")
						{
							return $this->readClose($node, $start, $open);
						}
						else
						{
							$type = Parser::ATTR_NAME;
							$attr = new Attr();
							continue;
						}
					}
				}
				else if($one === "}")
				{
					return $this->readClose($node, $start, $open);
				}
				else
				{
					break;
				}
			}

			// ATTR_SEARCH_VALUE or ATTR_ARRAY

			else
			{
				$array = $type === Parser::ATTR_ARRAY;

				// string
				if($one === '"' || $one === "'")
				{
					$open = $this->attrText($attr, $open);
				}

				// int
				else if($one === '-' || $one === '.' || ctype_digit($one))
				{
					$open = $this->attrNumber($attr, $open);
				}

				else if(ctype_alpha($one))
				{
					$open = $this->attrScal($attr, $open);
				}

				else if($one === '[' && ! $array)
				{
					$attr->type  = Attr::T_ARRAY;
					$attr->value = [];
					$open = $this->fill($open + 1);
					$type = self::ATTR_ARRAY;
					continue;
				}

				else if($one === '{' && $open + 1 < $this->length && $this->text[$open + 1] === '{')
				{
					$open = $this->attrNode($attr, $open);
				}

				else if($one !== ']' || ! $array)
				{
					break;
				}

				if($open !== false)
				{
					if($array)
					{
						$one = $this->text[$open];
						if($one === ",")
						{
							$open = $this->fill($open + 1);
							continue;
						}

						if($one === "]")
						{
							$open = $this->fill($open + 1);
						}
						else
						{
							break;
						}
					}

					$type = Parser::ATTR_NAME;
					$attr = new Attr();
				}
			}
		}

		return $this->readAbort($start, $open === false ? $prev : $open, $node);
	}

	private function attrNode(Attr $attr, int $open)
	{
		$node = $this->read($open);

		if($node->type === Node::T_TEXT)
		{
			return false;
		}

		if($attr->type === Attr::T_ARRAY)
		{
			$attr->value[] = $node;
		}
		else
		{
			$attr->type = Attr::T_NODE;
			$attr->value = $node;
		}

		return $this->fill($open + strlen($node->text));
	}

	private function attrScal(Attr $attr, int $start)
	{
		$val = "";
		while($start + 1 < $this->length)
		{
			$one = $this->text[$start++];
			if(ctype_alpha($one))
			{
				$val .= $one;
			}
			else
			{
				break;
			}
		}

		$val = strtolower($val);

		if($val === "true") $val = true;
		else if($val === "false") $val = false;
		else if($val === "null") $val = null;
		else return false;

		if($attr->type === Attr::T_ARRAY)
		{
			$attr->value[] = $val;
		}
		else
		{
			$attr->value = $val;
		}

		return $this->fill($start);
	}

	private function attrText(Attr $attr, int $start)
	{
		$one = $this->text[$start ++];
		$attr->quote = $one;
		$text = "";

		while($start + 2 < $this->length)
		{
			$one = $this->text[$start];
			if($one === $attr->quote)
			{
				$start ++;

				// escape
				if($this->text[$start] === $attr->quote)
				{
					$text .= $one;
				}
				else
				{
					if($attr->type === Attr::T_ARRAY)
					{
						$attr->value[] = $text;
					}
					else
					{
						$attr->value = $text;
					}

					return $this->fill($start);
				}
			}
			else
			{
				$text .= $one;
			}

			$start ++;
		}

		return false;
	}

	private function attrNumber(Attr $attr, int $start)
	{
		$val = "";
		$dot = false;

		if($this->text[$start] === "-")
		{
			$val .= "-";
			$start ++;
		}

		if($start + 1 < $this->length && $this->text[$start] === ".")
		{
			$val .= "0.";
			$dot = true;
			$start ++;
		}

		while($start + 1 < $this->length)
		{
			$one = $this->text[$start];
			if(ctype_digit($one))
			{
				$val .= $one;
			}
			else if($one === "." && !$dot)
			{
				$dot = true;
				$val .= $one;
			}
			else
			{
				break;
			}

			$start ++;
		}

		$start = $this->fill($start);
		if($start === false || ! is_numeric($val))
		{
			return false;
		}

		$val = $dot ? (float) $val : (int) $val;
		if($attr->type === Attr::T_ARRAY)
		{
			$attr->value[] = $val;
		}
		else
		{
			$attr->value = $val;
		}

		return $start;
	}

	private function readClose(Node $node, int $start, int $cursor): Node
	{
		$cursor += 1;
		if( $cursor < $this->length && $this->text[$cursor] === "}")
		{
			$node->text = substr($this->text, $start, $cursor + 1 - $start);
			return $node;
		}
		else
		{
			return $this->readAbort($start, $cursor - 1, $node);
		}
	}

	private function readAbort(int $start, int $last, ?Node $node = null): Node
	{
		if(is_null($node))
		{
			$node = new Node();
		}
		else
		{
			$node->type = Node::T_TEXT;
		}
		$node->text = substr($this->text, $start, $last - $start);
		return $node;
	}

	private function fill( int $start )
	{
		while($start + 1 < $this->length)
		{
			$cur = ord($this->text[$start]);
			if($cur > 32 && $cur !== 127)
			{
				return $start;
			}
			else
			{
				$start ++;
			}
		}

		return false;
	}
}