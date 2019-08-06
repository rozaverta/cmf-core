<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.06.2019
 * Time: 10:10
 */

namespace RozaVerta\CmfCore\Database\Query\Grammars;

use RozaVerta\CmfCore\Database\Expression;
use RozaVerta\CmfCore\Database\Grammar;
use RozaVerta\CmfCore\Database\Query\PlainBuilder;

/**
 * Class SqlServerGrammar
 *
 * @package RozaVerta\CmfCore\Database\Query\Grammars
 */
class SqlServerGrammar extends Grammar
{
	/**
	 * @var string
	 */
	protected $leftWrap = "[";

	/**
	 * @var string
	 */
	protected $rightWrap = "]";

	/**
	 * Compile the random statement into SQL.
	 *
	 * @param string $seed
	 * @return string
	 */
	public function orderByRandom( $seed )
	{
		return 'NEWID()';
	}

	/**
	 * Compile an exists statement into SQL.
	 *
	 * @param PlainBuilder $builder
	 * @param string|null  $select
	 * @param array|null   $params
	 *
	 * @return string
	 *
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function compileExists( PlainBuilder $builder, ?string $select = null, &$params = null )
	{
		if( empty( $select ) )
		{
			$select = '1 [exists]';
		}
		else if( is_array( $select ) )
		{
			$select[] = new Expression( '1 [exists]' );
		}
		else
		{
			$select = ( (string) $select ) . ', 1 [exists]';
		}

		$existsBuilder = clone $builder;
		$params = $existsBuilder->getParameters();
		return $existsBuilder->limit( 1 )->getSqlForSelect( $select );
	}

	/**
	 * Create full table name from string.
	 *
	 * @param string      $table
	 * @param string|null $alias
	 *
	 * @return string
	 */
	protected function wrapTableFull( string $table, ?string $alias ): string
	{
		$table = parent::wrapTableFull( $table, null );
		$value = $this->wrap( $this->tablePrefix . $table );

		if( preg_match( '/^(.+?)(\(.*?\))]$/', $value, $m ) === 1 )
		{
			$value = $m[1] . ']' . $m[2];
		}

		if( !empty( $alias ) )
		{
			$value .= " AS " . $this->wrapOne( $value );
		}

		return $value;
	}
}