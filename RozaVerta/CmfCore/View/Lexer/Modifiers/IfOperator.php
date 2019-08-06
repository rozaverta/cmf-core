<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 19.03.2019
 * Time: 12:08
 */

namespace RozaVerta\CmfCore\View\Lexer\Modifiers;

use RozaVerta\CmfCore\View\Interfaces\ModifierInterface;

class IfOperator implements ModifierInterface
{
	public function format( $value, array $attributes = [] )
	{
		// if=[ operator, comparator[, result, elseResult] ]

		$len = count($attributes);
		if( $len < 1 )
		{
			return $value;
		}

		$operator = array_shift($attributes);
		$len --;

		if( is_string($operator) )
		{
			$operator = strtolower($operator);
			if($operator === 'empty')
			{
				return $this->isEmpty($value, $attributes, $len);
			}
			if($operator === 'notempty')
			{
				return $this->notEmpty($value, $attributes, $len);
			}
		}

		if( $len < 1 )
		{
			$comparator = $operator;
			$operator = "eq";
			$then = $value;
			$else = null;
		}
		else
		{
			$comparator = $attributes[0];
			$then = $len > 1 ? $attributes[1] : $value;
			$else = $len > 2 ? $attributes[2] : null;
		}

		switch($operator)
		{
			case "<":
			case "lt":
				$test = $value < $comparator;
				break;

			case "<=":
			case "lte":
				$test = $value <= $comparator;
				break;

			case ">":
			case "gt":
				$test = $value > $comparator;
				break;

			case ">=":
			case "gte":
				$test = $value >= $comparator;
				break;

			case "=":
			case "==":
			case "eq":
				$test = $value == $comparator;
				break;

			case "!=":
			case "<>":
			case "neq":
				$test = $value != $comparator;
				break;

			case "===":
				$test = $value === $comparator; break;

			case "!==":
				$test = $value !== $comparator; break;

			default:
				$test = false;
				break;
		}

		return $test ? $then : $else;
	}

	protected function isEmpty($value, $attributes, $length)
	{
		if(empty($value) && $value !== "0")
		{
			return $length > 0 ? $attributes[0] : $value;
		}
		else
		{
			return $length > 1 ? $attributes[1] : null;
		}
	}

	protected function notEmpty($value, $attributes, $length)
	{
		if(empty($value) && $value !== "0")
		{
			return $length > 1 ? $attributes[1] : null;
		}
		else
		{
			return $length > 0 ? $attributes[0] : $value;
		}
	}
}