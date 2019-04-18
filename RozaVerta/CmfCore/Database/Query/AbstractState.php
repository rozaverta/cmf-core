<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 20:38
 */

namespace RozaVerta\CmfCore\Database\Query;

use Countable;
use Doctrine\DBAL\Types\Type as DbalType;
use RozaVerta\CmfCore\Helper\Str;

abstract class AbstractState extends AbstractBuilderContainer implements Countable
{
	protected $data = [];

	protected $events = [];

	/**
	 * @var \RozaVerta\CmfCore\Database\Scheme\Table|null
	 */
	protected $tableSchema = null;

	public function __construct( Builder $builder )
	{
		parent::__construct( $builder );
		$this->tableSchema = $builder->getTableSchema();
	}

	/**
	 * Set data values
	 *
	 * @param array $data
	 * @return $this
	 */
	public function values( array $data )
	{
		foreach( $data as $column => $value )
		{
			$this->set( $column, $value );
		}

		return $this;
	}

	abstract protected function setBuilder( string $column, $value );

	abstract protected function getCompleteHookName(): string;

	public function set( string $column, $value, & $bindName = null )
	{
		$type = null;

		if( $this->tableSchema )
		{
			if($this->tableSchema->exists($column))
			{
				$type = $this->tableSchema->column($column)->getType();
			}
			else
			{
				return $this;
			}
		}
		else
		{
			if( is_int($value) ) $type = DbalType::INTEGER;
			else if( is_float($value) ) $type = DbalType::FLOAT;
			else if( is_bool($value) ) $type = DbalType::BOOLEAN;
			else if( $value instanceof \DateTime ) $type = DbalType::DATETIME;
			else if( ! is_null($value) ) $type = DbalType::STRING;
		}

		if( ! isset($this->data[$column]) )
		{
			$this->data[$column] = $this->createParameterName($column);
			$this->setBuilder($column, ":" . $this->data[$column]);
		}
		else if( $this->data[$column] === ":expr" )
		{
			return $this;
		}

		$bindName = $this->data[$column];
		$this
			->dbalBuilder
			->setParameter($bindName, $this->hook("onPrepareValue", $column, $value), $type);

		return $this;
	}

	public function expr( string $column, string $value )
	{
		if( ! isset($this->data[$column]) )
		{
			$this->data[$column] = ":expr";
		}
		else if( $this->data[$column] !== ":expr" )
		{
			return $this;
		}

		$this->setBuilder($column, $value);

		return $this;
	}

	public function complete()
	{
		if($this->tableSchema)
		{
			foreach($this->tableSchema->getColumns() as $column)
			{
				$column = $column->getName();
				if( ! isset($this->data[$column]) )
				{
					$value = $this->hook($this->getCompleteHookName(), $column);
					if( $value !== null )
					{
						$this->set($column, $value);
					}
				}
			}
		}
	}

	public function createParameterName( string $name ): string
	{
		return str_replace(".", "_", Str::camel($name));
	}

	public function getColumnBindingNames(): array
	{
		return $this->data;
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
		return count($this->data);
	}

	protected function hook( string $hook, string $column, $value = null )
	{
		if( !$this->tableSchema || !$this->tableSchema->exists($column) )
		{
			return $value;
		}

		if( !isset($this->events[$column]) )
		{
			$hookClass = $this->tableSchema->column($column)->extra("hook");
			if( !$hookClass )
			{
				return $value;
			}

			$this->events[$column] = new $hookClass($this->tableSchema->getName(), $column);
		}

		return $hook === "onPrepareValue" ? $this->events[$column]->{$hook}($value) : $this->events[$column]->{$hook}();
	}
}