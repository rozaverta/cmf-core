<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 27.08.2018
 * Time: 15:48
 */

namespace RozaVerta\CmfCore\Language;

/**
 * Class ThenProxy
 *
 * @method string|array item( string $name, $default = '' )
 * @method string line( string $text )
 * @method string replace( string $text, ... $replace )
 * @method string choice( int $number, $string, array $replace = [] )
 * @method string text( string $text )
 * @method string transliterate( string $word, $latinOnly = true )
 *
 * @package RozaVerta\CmfCore\Language
 */
class ThenProxy
{
	/**
	 * @var LanguageManager
	 */
	protected $target;

	/**
	 * @var string
	 */
	protected $then;

	protected $proxy = ['item', 'line', 'replace', 'choice', 'text', 'transliterate'];

	public function __construct( LanguageManager $target, string $then )
	{
		$this->target = $target;
		$this->then = $then;
	}

	/**
	 * Dynamically pass method calls to the target.
	 *
	 * @param  string  $method
	 * @param  array   $parameters
	 * @return mixed
	 */
	public function __call($method, $parameters)
	{
		if( ! in_array($method, $this->proxy) )
		{
			throw new \BadFunctionCallException(get_class($this->target) . "::" . $method . ' is undefined or private, cannot used for proxy');
		}

		return $this
			->target
			->then($this->then)
			->{$method}(... $parameters);
	}
}