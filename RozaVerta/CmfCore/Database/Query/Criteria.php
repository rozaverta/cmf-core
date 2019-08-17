<?php

namespace RozaVerta\CmfCore\Database\Query;

use Closure;
use Countable;
use Doctrine\DBAL\Connection as DbalConnection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\Expression\ExpressionBuilder as DbalExpressionBuilder;
use Doctrine\DBAL\Query\Expression\CompositeExpression;
use RozaVerta\CmfCore\Database\Connection;

/**
 * Class Criteria
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
class Criteria extends AbstractConnectionContainer implements Countable
{
	public const TYPE_AND = CompositeExpression::TYPE_AND ;
	public const TYPE_OR  = CompositeExpression::TYPE_OR  ;

	public const EQ = DbalExpressionBuilder::EQ;
	public const NEQ = DbalExpressionBuilder::NEQ;
	public const LT = DbalExpressionBuilder::LT;
	public const LTE = DbalExpressionBuilder::LTE;
	public const GT = DbalExpressionBuilder::GT;
	public const GTE = DbalExpressionBuilder::GTE;

	private static $operators = [
		'not' => self::NEQ,
		'neq' => self::NEQ,
		'!='  => self::NEQ,
		'!==' => self::NEQ,
		'eq'  => self::EQ,
		'=='  => self::EQ,
		'lt'  => self::LT,
		'gt'  => self::GT,
		'lte' => self::LTE,
		'gte' => self::GTE,
		'le'  => self::LTE,
		'ge'  => self::GTE,
	];

	protected $parts = [];

	/**
	 * @var \Doctrine\DBAL\Query\Expression\ExpressionBuilder
	 */
	protected $expr;

	/**
	 * @var Parameters
	 */
	protected $parameters;

	/**
	 * @var string
	 */
	protected $type;

	/**
	 * @var null | Closure
	 */
	protected $renameClosure = null;

	public function __construct( Connection $connection, Parameters $parameters, $type = self::TYPE_AND )
	{
		parent::__construct( $connection );
		$this->expr = $this->dbalConnection->getExpressionBuilder();
		$this->type = $type === self::TYPE_OR ? self::TYPE_OR : self::TYPE_AND;
		$this->parameters = $parameters;
	}

	public function registerRenameClosure( Closure $rename )
	{
		$this->renameClosure = $rename;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @param Parameters $parameters
	 */
	public function setParameters( Parameters $parameters ): void
	{
		$this->parameters = $parameters;
	}

	/**
	 * @return Parameters
	 */
	public function getParameters(): Parameters
	{
		return $this->parameters;
	}

	/**
	 * Creates a conjunction of the given boolean expressions.
	 *
	 * @param Closure $closure
	 * @return Criteria
	 */
	public function orX( Closure $closure )
	{
		return $this->criteria( $closure, self::TYPE_OR );
	}

	/**
	 * Creates a conjunction of the given boolean expressions.
	 *
	 * @param Closure $closure
	 * @return Criteria
	 */
	public function andX( Closure $closure )
	{
		return $this->criteria( $closure, self::TYPE_AND );
	}

	public function each( array $data )
	{
		foreach($data as $name => $value)
		{
			if( ! is_int($name) )
			{
				$this->literal($name, self::EQ, $value);
			}
			else if( is_array($value) )
			{
				if(count($value) === 3 && $value[2] === null)
				{
					$this->operator($value[1]) === self::NEQ ? $this->isNotNull($value[0]) : $this->isNull($value[0]);
				}
				else
				{
					$this->literal( ... $value );
				}
			}
			else
			{
				$this->raw($value);
			}
		}

		return $this;
	}

	/**
	 * Creates an equality comparison expression with the given arguments.
	 *
	 * @param string $name The field name
	 * @param mixed $value The right expression value
	 * @param null $bindName
	 *
	 * @return Criteria
	 */
	public function eq($name, $value, & $bindName = null)
	{
		if( is_array($value) )
		{
			return $this->in($name, $value, $bindName);
		}

		$bindName = $this->bind($name, "eq", $value);
		return $this;
	}

	/**
	 * Creates a non equality comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generated a <left expr> <> <right expr>. Example:
	 *
	 * @param string $name The field name
	 * @param mixed $value The right expression value
	 * @param null $bindName
	 *
	 * @return Criteria
	 */
	public function neq($name, $value, & $bindName = null)
	{
		if( is_array($value) )
		{
			return $this->notIn($name, $value, $bindName);
		}

		$bindName = $this->bind($name, "neq", $value);
		return $this;
	}

	/**
	 * Creates a lower-than comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generated a <left expr> < <right expr>. Example:
	 *
	 * @param string $name The field name
	 * @param mixed $value The right expression value
	 * @param null $bindName
	 *
	 * @return Criteria
	 */
	public function lt($name, $value, & $bindName = null)
	{
		$bindName = $this->bind($name, "lt", $value);
		return $this;
	}

	/**
	 * Creates a lower-than-equal comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generated a <left expr> <= <right expr>. Example:
	 *
	 * @param string $name The field name
	 * @param mixed $value The right expression value
	 * @param null $bindName
	 *
	 * @return Criteria
	 */
	public function lte($name, $value, & $bindName = null)
	{
		$bindName = $this->bind($name, "lte", $value);
		return $this;
	}

	/**
	 * Creates a greater-than comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generated a <left expr> > <right expr>. Example:
	 *
	 * @param string $name The field name
	 * @param mixed $value The right expression value
	 * @param null $bindName
	 *
	 * @return Criteria
	 */
	public function gt($name, $value, & $bindName = null)
	{
		$bindName = $this->bind($name, "gt", $value);
		return $this;
	}

	/**
	 * Creates a greater-than-equal comparison expression with the given arguments.
	 * First argument is considered the left expression and the second is the right expression.
	 * When converted to string, it will generated a <left expr> >= <right expr>. Example:
	 *
	 * @param string $name The field name
	 * @param mixed $value The right expression value
	 * @param null $bindName
	 *
	 * @return Criteria
	 */
	public function gte($name, $value, & $bindName = null)
	{
		$bindName = $this->bind($name, "gte", $value);
		return $this;
	}

	/**
	 * Creates an IS NULL expression with the given arguments.
	 *
	 * @param string $x The field in string format to be restricted by IS NULL.
	 *
	 * @return $this
	 */
	public function isNull($x)
	{
		return $this->push(
			$this->expr->isNull( $this->rename($x) )
		);
	}

	/**
	 * Creates an IS NOT NULL expression with the given arguments.
	 *
	 * @param string $x The field in string format to be restricted by IS NOT NULL.
	 *
	 * @return $this
	 */
	public function isNotNull($x)
	{
		return $this->push(
			$this->expr->isNotNull( $this->rename($x) )
		);
	}

	/**
	 * Creates a LIKE() comparison expression with the given arguments.
	 *
	 * @param string $name Field in string format to be inspected by LIKE() comparison.
	 * @param mixed  $value Argument to be used in LIKE() comparison.
	 * @param null   $bindName
	 * @param array  $more
	 *
	 * @return Criteria
	 *
	 */
	public function like($name, $value, & $bindName = null, ... $more)
	{
		$bindName = $this->bind($name, 'like', $value, false, $more);
		return $this;
	}

	/**
	 * Creates a NOT LIKE() comparison expression with the given arguments.
	 *
	 * @param string $name Field in string format to be inspected by NOT LIKE() comparison.
	 * @param mixed  $value Argument to be used in NOT LIKE() comparison.
	 * @param null   $bindName
	 * @param array  $more
	 *
	 * @return Criteria
	 *
	 */
	public function notLike($name, $value, & $bindName = null, ... $more)
	{
		$bindName = $this->bind($name, 'notLike', $value, false, $more);
		return $this;
	}

	/**
	 * Creates a IN () comparison expression with the given arguments.
	 *
	 * @param string $name The field name
	 * @param mixed $value The right expression value
	 * @param null $bindName
	 *
	 * @return Criteria
	 */
	public function in($name, $value, & $bindName = null)
	{
		$bindName = $this->bind($name, 'in', $value, true);
		return $this;
	}

	/**
	 * Creates a NOT IN () comparison expression with the given arguments.
	 *
	 * @param string $name The field name
	 * @param mixed $value The right expression value
	 * @param null $bindName
	 *
	 * @return Criteria
	 */
	public function notIn($name, $value, & $bindName = null)
	{
		$bindName = $this->bind($name, 'notIn', $value, true);
		return $this;
	}

	/**
	 * Add comparison expression
	 *
	 * @param string $name The field name
	 * @param string $operator
	 * @param $value
	 * @param null $bindName
	 * @return Criteria
	 */
	public function add($name, $operator, $value, & $bindName = null)
	{
		$operator = $this->operator($operator);
		if(is_array($value))
		{
			if($operator === self::EQ || $operator === 'in')
			{
				return $this->in($name, $value, $bindName);
			}

			if($operator === self::NEQ || $operator === 'notin')
			{
				return $this->notIn($name, $value, $bindName);
			}
		}

		$bindValue = $this->parameters->bindNextForColumn( $value );
		$bindName = substr($bindValue, 1);
		$expr = $this->expr( $name, $operator, $bindValue );
		return empty($expr) ? $this : $this->push($expr);
	}

	/**
	 * Add columns comparison.
	 *
	 * @param string      $leftName
	 * @param string      $operator
	 * @param string|null $rightName
	 *
	 * @return $this
	 */
	public function columns( string $leftName, string $operator, ?string $rightName = null )
	{
		if( $rightName === null )
		{
			$rightName = $operator;
			$operator = self::EQ;
		}

		$this->push(
			$this->expr->comparison(
				$this->rename( $leftName ),
				$this->operator( $operator ),
				$this->rename( $rightName )
			)
		);

		return $this;
	}

	/**
	 * @param $name
	 * @param null $operator
	 * @param null $value
	 * @return Criteria
	 */
	public function raw($name, $operator = null, $value = null)
	{
		if( is_null($value) )
		{
			if( ! is_null($operator) )
			{
				$name = $this->expr->comparison($this->rename($name), self::EQ, $operator);
			}
		}
		else
		{
			$name = $this->expr->comparison($this->rename($name), $operator, $value);
		}

		return $this->push($name);
	}

	/**
	 * Quotes a given input parameter.
	 *
	 * @param string      $name  The field name
	 * @param string      $operator  One of the ExpressionBuilder::* constants
	 * @param mixed|null  $value The parameter to be quoted.
	 *
	 * @return string
	 */
	public function literal( $name, $operator, $value = null )
	{
		if( is_null($value) )
		{
			$value = $operator;
			$operator = self::EQ;
		}

		$expr = $this->expr( $name, $this->operator( $operator ), $this->expr->literal( $value ) );
		return empty($expr) ? $this : $this->push($expr);
	}

	public function getSql(): string
	{
		$len = count($this->parts);
		if( !$len )
		{
			return "";
		}

		if($len < 2)
		{
			return $this->parts[0];
		}

		return implode(" {$this->type} ", $this->parts);
	}

	public function __toString()
	{
		return $this->getSql();
	}

	/**
	 * Count elements of an object
	 * @link http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return count($this->parts);
	}

	protected function expr( $name, $operator, $value ): string
	{
		$expr = $this->expr;
		$name = $this->rename($name);

		switch($operator)
		{
			case self::EQ :
			case self::NEQ :
			case self::GT :
			case self::LT :
			case self::GTE :
			case self::LTE :
				return $expr->comparison($name, $operator, $value); break;

			case 'like' :
				return $expr->like($name, $value); break;

			case 'notlike' :
				return $expr->notLike($name, $value); break;
		}

		return "";
	}

	protected function bind( $name, $operator, $value, $arrayMode = false, array $more = [] )
	{
		if( $arrayMode )
		{
			$value = (array) $value;
			$type = is_int( end( $value ) ) ? DbalConnection::PARAM_INT_ARRAY : DbalConnection::PARAM_STR_ARRAY;
		}
		else
		{
			$type = ParameterType::STRING;
		}

		$bindValue = $this->parameters->bindNext( $value, $type );
		$this->parts[] = $this->expr->{$operator}($this->rename($name), $bindValue, ... $more);

		return substr($bindValue, 1);
	}

	protected function criteria( Closure $closure, $type )
	{
		$criteria = new Criteria( $this->connection, $this->parameters, $type );
		$criteria->renameClosure = $this->renameClosure;
		$closure($criteria);

		$cnt = $criteria->count();
		if($cnt > 1)
		{
			$this->parts[] = "( " . $criteria->getSql() . " )";
		}
		else if($cnt > 0)
		{
			$this->parts[] = $criteria->getSql();
		}

		return $this;
	}

	protected function push( $exp )
	{
		$this->parts[] = $exp;
		return $this;
	}

	protected function rename( string $name ): string
	{
		$name = $this->renameClosure === null ? $name : (string) call_user_func( $this->renameClosure, $name );
		return $this->grammar->wrap( $name );
	}

	protected function operator( $operator ): string
	{
		if(empty($operator))
		{
			return self::EQ;
		}

		$operator = str_replace(" ", "", strtolower($operator));
		return self::$operators[$operator] ?? $operator;
	}
}