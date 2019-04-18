<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2017
 * Time: 0:45
 */

namespace RozaVerta\CmfCore\Log;

use Exception;
use JsonSerializable;
use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Interfaces\Arrayable;

/**
 * Class Log
 *
 * @package RozaVerta\CmfCore\Log
 */
class Log implements Arrayable, JsonSerializable
{
	protected $text;

	protected $level = Logger::ERROR;

	protected $code = "LOG";

	protected $replacement = [];

	protected $timestamp;

	protected $inline = false;

	public function __construct( string $text, int $level = Logger::ERROR, ?string $code = null )
	{
		$this->text = $text;
		$this->timestamp = time();

		if( $level > 0 )
		{
			$this->level = $level;
		}

		if( $code )
		{
			$this->code = is_numeric($code) ? "CODE_{$code}" : $code;
		}
	}

	public function bounceBack()
	{
		if( ! count($this->replacement) )
		{
			$this->replacement = [];
			$this->text = preg_replace_callback(
				'/\'(.*?)\'/',
				function($m) {
					$value = trim($m[1]);
					if( is_numeric($value) )
					{
						$value = strpos($value, ".") === false ? (int) $value : (float) $value;
					}
					$this->replacement[] = $value;
					return is_numeric($value) ? "'%d'" : "'%s'";
				},
				$this->text,
				-1,
				$count
			);
		}

		return $this;
	}

	public function translate( string $context = null )
	{
		$lang = App::getInstance()->lang;
		if( ! is_null($context) )
		{
			$lang->then($context);
		}

		try {
			$this->text = $lang->line($this->text);
		}
		catch( Exception $e ) {}

		return $this;
	}

	/**
	 * Is inline mode
	 *
	 * @return bool
	 */
	public function isInline(): bool
	{
		return $this->inline;
	}

	/**
	 * Set inline mode
	 *
	 * @param bool $inline
	 * @return $this
	 */
	public function setInline( bool $inline = true)
	{
		$this->inline = $inline;
		return $this;
	}

	public function message(): string
	{
		if( count($this->replacement) )
		{
			$text = vsprintf( $this->text, $this->replacement );
		}
		else
		{
			$text = $this->text;
		}

		if($this->inline)
		{
			$text = str_replace(["\r\n", "\n", "\r"], " ", $text);
		}

		return $text;
	}

	/**
	 * @return string
	 */
	public function getText(): string
	{
		return $this->text;
	}

	/**
	 * @return int
	 */
	public function getLevel()
	{
		return $this->level;
	}

	/**
	 * @return int
	 */
	public function getLevelName()
	{
		return Logger::getLevelName($this->level);
	}

	/**
	 * @return string
	 */
	public function getCode(): string
	{
		return $this->code;
	}

	/**
	 * @param array $replacement
	 * @return $this
	 */
	public function setReplacement( array $replacement )
	{
		$this->replacement = $replacement;
		return $this;
	}

	/**
	 * @return array
	 */
	public function getReplacement()
	{
		return $this->replacement;
	}

	/**
	 * @return int
	 */
	public function getTimestamp()
	{
		return $this->timestamp;
	}

	public function __toString()
	{
		$text = "";
		if($this->inline)
		{
			$text  = "- " . date( "Y-m-d H:i:s", $this->timestamp ) . ' [' . $this->getLevelName() . '] ';
			$text .= $this->code . ' ';
		}

		$text .= $this->message();
		return $text;
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray(): array
	{
		return [
			"level"   => Logger::getLevelName($this->level),
			"message" => $this->message(),
			"time"    => $this->timestamp,
			"code"    => $this->code
		];
	}

	/**
	 * Specify data which should be serialized to JSON
	 *
	 * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
	 * @return mixed data which can be serialized by <b>json_encode</b>,
	 * which is a value of any type other than a resource.
	 * @since 5.4.0
	 */
	public function jsonSerialize()
	{
		return $this->toArray();
	}
}