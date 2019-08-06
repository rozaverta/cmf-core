<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:45
 */

namespace RozaVerta\CmfCore\Support;

class Text
{
	/**
	 * Text line
	 *
	 * @var string
	 */
	protected $text;

	/**
	 * Replacement
	 *
	 * @var array
	 */
	protected $replacement = [];

	public function __construct( $text, ... $args )
	{
		$this->text = (string) $text;
		if( $num = count($args) > 0 )
		{
			$this->text = preg_replace( '/(?![\'"])%([sd])/', '"%$1"', $this->text );
			$this->replacement = $num == 1 && is_array($args[0]) ? $args[0] : $args;
		}
	}

	/**
	 * @return string
	 */
	public function getText(): string
	{
		return $this->text;
	}

	/**
	 * @return array
	 */
	public function getReplacement(): array
	{
		return $this->replacement;
	}

	public static function text( $text, ... $args ): Text
	{
		return new Text( $text, ... $args );
	}

	public function __toString()
	{
		return count($this->replacement ) < 1 ? $this->text : vsprintf($this->text, $this->replacement);
	}
}