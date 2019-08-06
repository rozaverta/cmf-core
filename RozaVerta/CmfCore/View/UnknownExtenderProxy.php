<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 07.04.2019
 * Time: 13:00
 */

namespace RozaVerta\CmfCore\View;

class UnknownExtenderProxy
{
	private $name;

	private $calls = [];

	public function __construct( $name, View $view )
	{
		$this->name = $name;
	}

	public function __call( $name, $arguments )
	{
		$this->calls[] = $name . '(' . count($arguments) . ')';
		return $this;
	}

	public function __toString()
	{
		$text = "Unknown extender '{$this->name}'";
		if(count($this->calls))
		{
			$text .= ', calls: ' . implode(' -> ', $this->calls);
		}
		return $text;
	}
}