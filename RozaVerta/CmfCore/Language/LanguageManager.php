<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.01.2015
 * Time: 15:59
 */

namespace RozaVerta\CmfCore\Language;

use RozaVerta\CmfCore\Language\Interfaces\ChoiceLocaleInterface;
use RozaVerta\CmfCore\Language\Interfaces\TextInterface;
use RozaVerta\CmfCore\Language\Interfaces\TransliterationInterface;
use RozaVerta\CmfCore\Language\Locale\Dumper;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Helper\Str;
use RozaVerta\CmfCore\Traits\ApplicationTrait;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;
use RozaVerta\CmfCore\Language\Events\ReadyLanguageEvent;
use RozaVerta\CmfCore\Language\Events\SelectLanguageEvent;

/**
 * Class LanguageManager
 *
 * @method static LanguageManager getInstance()
 * @package RozaVerta\CmfCore\Language
 */
final class LanguageManager
{
	use SingletonInstanceTrait;
	use ApplicationTrait;

	/**
	 * Current language
	 *
	 * @var string
	 */
	private $language = "en";

	/**
	 * @var Language | TextInterface | TransliterationInterface
	 */
	private $lang;

	/**
	 * @var ThenProxy[]
	 */
	private $proxy = [];

	/**
	 * @var ChoiceLocaleInterface
	 */
	private $locale;

	private $lang_transliteration = false;
	private $lang_text = false;
	private $lang_default = "en";
	private $lang_is_default = true;
	private $lang_init = false;

	private $package_context = "default";

	protected function __construct()
	{
		$this->appInit();

		// set default
		$this->language = Prop::prop("system")->getOr("language", $this->lang_default);
		$this->lang_default = $this->language;

		$event = new ReadyLanguageEvent($this);
		$this->app->event->dispatch($event);

		$this->reload($event->language);
	}

	/**
	 * Get current language key
	 *
	 * @return string
	 */
	public function getCurrent(): string
	{
		return $this->language;
	}

	/**
	 * Get default language key
	 *
	 * @return string
	 */
	public function getDefault(): string
	{
		return $this->lang_default;
	}

	/**
	 * Current language is default
	 *
	 * @return bool
	 */
	public function currentIsDefault(): bool
	{
		return $this->lang_is_default;
	}

	/**
	 * Load language package
	 *
	 * @param string $package_name
	 * @return bool
	 */
	public function load( string $package_name ): bool
	{
		return $this->lang->load($package_name);
	}

	/**
	 * @param ChoiceLocaleInterface $locale
	 * @return $this
	 */
	public function setLocale( ChoiceLocaleInterface $locale )
	{
		$this->locale = $locale;
		return $this;
	}

	/**
	 * @return ChoiceLocaleInterface
	 */
	public function getLocale(): ChoiceLocaleInterface
	{
		return $this->locale;
	}

	/**
	 * Set new language key and reload all packages
	 *
	 * @param string $language
	 * @return bool
	 */
	public function reload( string $language ): bool
	{
		$language = trim( $language );
		if( ! self::valid($language) )
		{
			return false;
		}

		if( $this->language === $language && $this->lang_init )
		{
			return true;
		}

		$packages = is_null($this->lang) ? [] : $this->lang->packages();

		$this->locale = Dumper::getLocale($language);
		$this->language = $language;
		$this->lang = null;
		$this->lang_init = true;
		$this->proxy = [];

		$event = new SelectLanguageEvent($this);
		$this->app->event->dispatch(
			$event,
			function( $result ) use($event) {
				if( $result instanceof Language )
				{
					$this->lang = $result;
					$event->stopPropagation();
				}
			});

		if( is_null($this->lang) )
		{
			$this->lang = new LanguageFiles($language);
		}

		$this->lang_transliteration = $this->lang instanceof TransliterationInterface;
		$this->lang_text = $this->lang instanceof TextInterface;
		$this->lang_is_default = $this->language === $this->lang_default;

		foreach($packages as $package)
		{
			$this->lang->load($package);
		}

		return true;
	}

	/**
	 * Set current package for ready next item
	 *
	 * @param string $context package name
	 * @return $this
	 */
	public function then( string $context = "default" )
	{
		$this->package_context = $context;
		return $this;
	}

	/**
	 * Get the language package proxy
	 *
	 * @param string $context
	 * @return ThenProxy
	 */
	public function getProxy( string $context ): ThenProxy
	{
		if( isset($this->proxy[$context]) )
		{
			return $this->proxy[$context];
		}

		if( !$this->lang->load($context) )
		{
			throw new \InvalidArgumentException("Cannot load the '{$context}' language package");
		}

		$this->proxy[$context] = new ThenProxy($this, $context);
		return $this->proxy[$context];
	}

	/**
	 * Get item
	 *
	 * @param string $name
	 * @param string $default
	 * @return string|array
	 */
	public function item( string $name, $default = '' )
	{
		return $this->lang->item($name, $this->getThen(), $default);
	}

	/**
	 * Get line (convert item to string)
	 *
	 * @param string $text
	 * @return string
	 */
	public function line( string $text ): string
	{
		$line = $this->lang->item($text, $this->getThen(), $text);
		return is_array($line) ? reset($line) : (string) $line;
	}

	/**
	 * Get line and replace values
	 *
	 * @param string $text
	 * @param array ...$replace
	 * @return string
	 */
	public function replace( string $text, ... $replace ): string
	{
		$then = $this->package_context;
		return $this->format(
			$this->line($text),
			$then,
			count($replace) === 1 && is_array($replace[0]) ? $replace[0] : $replace
		);
	}

	public function choice( int $number, $string, array $replace = [] ): string
	{
		$then = $this->getThen();

		if( is_string( $string ) && $this->lang->itemIs($string, $then) )
		{
			$string = $this->lang->item($string, $then);
		}

		if( is_array( $string ) )
		{
			$string = $string[$this->locale->getRule($number)] ?? current($string);
		}
		else
		{
			$string = $this->locale->getNameRule((string) $string, $number);
		}

		$pos = strpos( $string, "%d" );
		if( $pos !== false )
		{
			$string = substr_replace( $string, $number, $pos, 2 );
		}

		if( ! empty($replace) )
		{
			$string = $this->format( $string, $then, $replace );
		}

		return $string;
	}

	/**
	 * Get text block
	 *
	 * @param string $text
	 * @return string
	 */
	public function text( string $text ): string
	{
		$then = $this->getThen();
		if( $this->lang_text )
		{
			return $this->lang->text($text, $then);
		}
		else
		{
			return $text;
		}
	}

	/**
	 * Transliterate string
	 *
	 * @param string $word
	 * @param bool $latinOnly
	 * @return string
	 */
	public function transliterate( string $word, $latinOnly = true ): string
	{
		if( $this->lang_transliteration )
		{
			$word = $this->lang->transliterate($word);
		}
		else
		{
			$word = Str::ascii($word, $this->language);
		}

		if( $latinOnly )
		{
			$word = preg_replace('/[^\x00-\xff]+/u', '', $word);
			$word = trim( preg_replace('/\s+/', ' ', $word ) );
		}

		return $word;
	}

	/**
	 * Validate language key
	 *
	 * @param string $language
	 * @return bool
	 */
	public static function valid( string $language ): bool
	{
		return strlen($language) >= 2 && preg_match('/^[a-z]{2}(?:_[a-zA-Z]{2,5})?(?:\-[a-zA-Z0-9_]+)?$/', $language);
	}

	private function format( string $text, string $then, array $replace ): string
	{
		$new_text = "";
		$num = 0;
		$len = count($replace);

		for(;;)
		{
			$pos = strpos( $text, "%", 0 );
			if( $pos === false )
			{
				$new_text .= $text;
				break;
			}

			if( $pos > 0 )
			{
				$new_text .= substr( $text, 0, $pos );
			}

			if( strlen( $text ) < 2 )
			{
				break;
			}

			$text = substr( $text, $pos + 1 );
			if( $text[0] == "s" )
			{
				$new_text .= $num < $len ? @ $replace[$num++] : "";
				$text = substr( $text, 1 );
			}

			else if( $text[0] == "d" )
			{
				$i18n = $num < $len ? @ $replace[$num++] : 0;
				if( preg_match( '/^d-\((.*?)\)/', $text, $m ) )
				{
					$new_text .= $this
						->then($then)
						->choice( (int) $i18n, trim( $m[1] ) );

					$text = substr( $text, strlen( $m[0] ) );
				}
				else
				{
					$new_text .= $i18n;
					$text = substr( $text, 1 );
				}
			}

			else if( preg_match( '/^(\d+)(s|d)/', $text, $m ) )
			{
				$int = (int) $m[1];
				if( $int > 0 ) $int --;
				$val = isset( $replace[$int] ) ? $replace[$int] : "";

				$text = substr( $text, strlen( $m[0] ) );
				if( $m[2] == "s" )
				{
					$new_text .= $val;
				}
				else
				{
					$val = (int) $val;
					if( preg_match( '/^-\((.*?)\)/', $text, $m ) )
					{
						$val = $this
							->then($then)
							->choice( $val, $m[1] );

						$text = substr( $text, strlen( $m[0] ) );
					}
					$new_text .= $val;
				}
			}
		}

		return $new_text;
	}

	private function getThen()
	{
		$then = $this->package_context;
		$this->package_context = "default";
		return $then;
	}
}