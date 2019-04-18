<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.03.2019
 * Time: 21:38
 */

namespace RozaVerta\CmfCore\View\Lexer;

class Node
{
	const T_TEXT = 1;
	const T_VAR  = 2;
	const T_TAG  = 3;

	public $type = self::T_TEXT;
	public $name = "";

	/**
	 * @var Attr[]
	 */
	public $attr = [];
	public $text = '';

	public function __construct(int $type = self::T_TEXT, string $text = "", string $name = "")
	{
		$this->type = $type;
		$this->text = $text;
		$this->name = $name;
	}

	static public function toText(Node $node, $mode = 'format'): string
	{
		if($mode === true)
		{
			return $node->text;
		}

		$attr = $node->attr;
		if($mode === 'format-shift')
		{
			$mode = 'format';
			unset($attr['raw']);
		}

		$text  = '{{ ';
		$text .= $node->type === Node::T_VAR ? '$' : '';
		$text .= $node->name;

		if(count($attr))
		{
			/** @var Attr $at */
			foreach( $attr as $name => $at)
			{
				$text .= ' ' . Attr::toText($at);
			}
		}
		$text .= ' }}';

		if($mode !== 'format' && strpos($mode, '%s') !== false)
		{
			$text = sprintf($mode, $text);
		}

		return $text;
	}
}