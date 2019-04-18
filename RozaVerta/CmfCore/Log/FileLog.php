<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.08.2018
 * Time: 20:55
 */

namespace RozaVerta\CmfCore\Log;

/**
 * Class FileLog
 *
 * @package RozaVerta\CmfCore\Log
 */
class FileLog extends Log
{
	protected $file;

	protected $line;

	public function __construct( $text, int $level = Logger::ERROR, ?string $code = null, string $file = __FILE__, int $line = __LINE__ )
	{
		parent::__construct( $text, $level, $code );
		$this->file = $file;
		$this->line = $line;
	}

	/**
	 * @return string
	 */
	public function getFile(): string
	{
		return $this->file;
	}

	/**
	 * @return int
	 */
	public function getLine(): int
	{
		return $this->line;
	}

	public function message(): string
	{
		$text  = parent::message();
		$text .= ', file ' . $this->getFile();
		$text .= ', line ' . $this->getLine();
		return $text;
	}
}