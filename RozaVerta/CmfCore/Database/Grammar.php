<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.06.2019
 * Time: 8:52
 */

namespace RozaVerta\CmfCore\Database;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type as DbalType;
use RozaVerta\CmfCore\Database\Query\PlainBuilder;

/**
 * Class Grammar
 *
 * @package RozaVerta\CmfCore\Database
 */
class Grammar
{
	/**
	 * @var string
	 */
	protected $tablePrefix;

	/**
	 * @var AbstractPlatform
	 */
	protected $platform;

	/**
	 * @var string
	 */
	protected $leftWrap = '"';

	/**
	 * @var string
	 */
	protected $rightWrap = '"';

	/**
	 * Grammar constructor.
	 *
	 * @param Connection $connection
	 *
	 * @throws DBALException
	 */
	public function __construct( Connection $connection )
	{
		$this->tablePrefix = $connection->getTablePrefix();
		$this->platform = $connection->getDbalDatabasePlatform();
	}

	/**
	 * Wrap a table in keyword identifiers.
	 *
	 * @param Expression|string $table
	 * @param string|null       $alias
	 *
	 * @return string
	 */
	public function wrapTable( $table, ? string $alias = null ): string
	{
		if( $table instanceof Expression )
		{
			$table = $table->getValue();
			if( !empty( $alias ) && stripos( $table, ' AS ' ) === false )
			{
				$table .= ' AS ' . $this->wrapOne( $alias );
			}
		}
		else
		{
			$table = $this->wrapTableFull( (string) $table, $alias );
		}

		return $table;
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
		$table = $this->tablePrefix . $table;
		$table = empty( $alias ) ? $this->wrap( $table ) : $this->wrapAs( $table, $alias );
		return $table;
	}

	/**
	 * Wrap a value in keyword identifiers.
	 *
	 * @param Expression|string $value
	 * @return string
	 */
	public function wrap( $value ): string
	{
		if( $value instanceof Expression )
		{
			return $value->getValue();
		}

		$alias = false;
		$pos = stripos( $value, ' AS ' );
		if( $pos !== false )
		{
			$alias = ltrim( substr( $value, $pos + 4 ) );
			$value = rtrim( substr( $value, 0, $pos ) );
		}

		// check function
		if( strpos( $value, '(' ) !== false )
		{
			if( strpos( $value, "." ) !== false )
			{
				$value = implode( ".", array_map( [ $this, 'wrapOne' ], explode( ".", $value ) ) );
			}
			else
			{
				$value = $this->wrapOne( $value );
			}
		}

		if( $alias )
		{
			$value .= " AS " . $this->wrapOne( $alias );
		}

		return $value;
	}

	/**
	 * Wrap a value in keyword identifiers with alias.
	 *
	 * @param Expression|string $value
	 * @param string            $alias
	 *
	 * @return string
	 */
	public function wrapAs( $value, string $alias ): string
	{
		return $this->wrap( $value ) . " AS " . $this->wrapOne( $alias );
	}

	/**
	 * Wrap a value if it has not already been wrapped.
	 *
	 * @param Expression|string $value
	 *
	 * @return mixed|string
	 */
	public function wrapSafe( $value )
	{
		if( $value instanceof Expression )
		{
			return $value->getValue();
		}
		else
		{
			return $this->wasWrapped( $value ) ? $value : $this->wrap( $value );
		}
	}

	/**
	 * Convert an array of column names into a delimited string.
	 *
	 * @param array $columns
	 * @return string
	 */
	public function columnize( array $columns )
	{
		return implode( ', ', array_map( [ $this, 'wrap' ], $columns ) );
	}

	/**
	 * Wrap an array of values.
	 *
	 * @param array $values
	 * @return array
	 */
	public function wrapArray( array $values )
	{
		return array_map( [ $this, 'wrap' ], $values );
	}

	/**
	 * Wrap a single string in keyword identifiers.
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	protected function wrapOne( string $value ): string
	{
		if( $value !== '*' && $this->leftWrap !== false )
		{
			$right2 = $this->rightWrap . $this->rightWrap;
			return $this->leftWrap . str_replace( $this->rightWrap, $right2, $value ) . $this->rightWrap;
		}
		return $value;
	}

	/**
	 * Check if the string was wrapped.
	 *
	 * @param string $value
	 * @return bool
	 */
	protected function wasWrapped( string $value ): bool
	{
		if( $this->leftWrap === false )
		{
			return true;
		}

		$value = trim( $value );
		$len = strlen( $value );
		if( $len < 1 )
		{
			return false;
		}

		if( $value[0] === $this->leftWrap && $value[$len - 1] === $this->rightWrap )
		{
			return true;
		}

		$pos = stripos( $value, ' AS ' );
		if( $pos !== false )
		{
			// check variant: COUNT(value) as value2

			$alias = ltrim( substr( $value, $pos + 4 ) );
			$value = rtrim( substr( $value, 0, $pos ) );
		}
		else
		{
			// check function

			return strpos( $value, '(' ) === false;
		}

		return strpos( $value, '(' ) === false ? false : $this->wasWrapped( $alias );
	}

	/**
	 * Compile an exists statement into SQL.
	 *
	 * @param PlainBuilder $builder
	 * @param string|null  $select
	 *
	 * @param null         $params
	 * @return string
	 *
	 * @throws DBALException
	 */
	public function compileExists( PlainBuilder $builder, ?string $select = null, &$params = null )
	{
		$query = $builder->getSqlForSelect( $select );
		$params = $builder->getParameters();
		return "SELECT EXISTS({$query}) AS {$this->wrap('exists')}";
	}

	/**
	 * Adds an driver-specific LIMIT clause to the query.
	 *
	 * @param string $query
	 * @param int    $limit
	 * @param int    $offset
	 *
	 * @return string
	 *
	 * @throws DBALException
	 */
	public function modifyLimitQuery( string $query, ? int $limit, ? int $offset = null )
	{
		return $this
			->platform
			->modifyLimitQuery( $query, $limit, $offset );
	}

	/**
	 * Create a new Expression(). Replace and wrap strings.
	 *
	 * @param string $value
	 * @param array  $wrap
	 *
	 * @return Expression
	 */
	public function newExpression( string $value, array $wrap = [] )
	{
		if( count( $wrap ) )
		{
			$value = vsprintf( $value, $this->wrapArray( $wrap ) );
		}
		return new Expression( $value );
	}

	/**
	 * Determine if the given value is a raw expression.
	 *
	 * @param mixed $value
	 * @return bool
	 */
	public function isExpression( $value )
	{
		return $value instanceof Expression;
	}

	/**
	 * Get the grammar's table prefix.
	 *
	 * @return string
	 */
	public function getTablePrefix()
	{
		return $this->tablePrefix;
	}

	/**
	 * Get parameter value type (auto detect)
	 *
	 * @param $value
	 *
	 * @return int|string
	 */
	public function valueType( $value )
	{
		if( is_int( $value ) ) $type = ParameterType::INTEGER;
		else if( is_bool( $value ) ) $type = ParameterType::BOOLEAN;
		else if( $value instanceof \DateTime ) $type = DbalType::DATETIME;
		else if( !is_null( $value ) ) $type = ParameterType::NULL;
		else $type = ParameterType::STRING;

		return $type;
	}

	/**
	 * Compile the random statement into SQL.
	 *
	 * @param string $seed
	 * @return string
	 */
	public function orderByRandom( $seed )
	{
		return 'RANDOM()';
	}
}