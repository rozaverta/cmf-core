<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 04.08.2019
 * Time: 22:08
 */

namespace RozaVerta\CmfCore\Database\Query;

use RozaVerta\CmfCore\Database\Expression;
use RozaVerta\CmfCore\Database\Scheme\Table;

/**
 * Class WriteableState
 *
 * @package RozaVerta\CmfCore\Database\Query
 */
class WriteableState
{
	protected $parameters = [];

	protected $types = [];

	/**
	 * @var Table
	 */
	protected $tableSchema;

	public function __construct( Table $schema )
	{
		$this->tableSchema = $schema;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}

	/**
	 * @return array
	 */
	public function getTypes(): array
	{
		return $this->types;
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

	public function set( string $column, $value )
	{
		if( $this->tableSchema->exists( $column ) )
		{
			$this->parameters[$column] = $value;
			$this->types[$column] = $this->tableSchema->column( $column )->getType();
		}

		return $this;
	}

	public function expr( string $column, string $value )
	{
		if( $this->tableSchema->exists( $column ) )
		{
			$this->parameters[$column] = new Expression( $value );
			unset( $this->types[$column] );
		}

		return $this;
	}

	/**
	 * Count elements of an object
	 *
	 * @link  http://php.net/manual/en/countable.count.php
	 * @return int The custom count as an integer.
	 * </p>
	 * <p>
	 * The return value is cast to an integer.
	 * @since 5.1.0
	 */
	public function count()
	{
		return count( $this->parameters );
	}
}