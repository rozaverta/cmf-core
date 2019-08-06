<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.06.2019
 * Time: 10:02
 */

namespace RozaVerta\CmfCore\Database\Query\Grammars;

use RozaVerta\CmfCore\Database\Grammar;

/**
 * Class Grammars
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
class MySqlGrammar extends Grammar
{
	/**
	 * @var string
	 */
	protected $leftWrap = "`";

	/**
	 * @var string
	 */
	protected $rightWrap = "`";

	/**
	 * Compile the random statement into SQL.
	 *
	 * @param string $seed
	 * @return string
	 */
	public function orderByRandom( $seed )
	{
		return 'RAND(' . $seed . ')';
	}
}