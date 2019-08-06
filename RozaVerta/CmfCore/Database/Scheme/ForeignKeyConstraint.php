<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.03.2019
 * Time: 0:34
 */

namespace RozaVerta\CmfCore\Database\Scheme;

class ForeignKeyConstraint extends AbstractKey
{
	protected $fkTableName = "";

	protected $fkColumns = [];

	protected $onDelete = null;

	protected $onUpdate = null;

	protected $default = null;

	public function __construct( ?string $name, array $columns, string $fkTableName, array $fkColumns, array $options = [] )
	{
		$this->name = $name;
		$this->columns = $columns;
		$this->fkTableName = $fkTableName;
		$this->fkColumns = $fkColumns;
		$this->onEvent("onUpdate", $options);
		$this->onEvent("onDelete", $options);
	}

	/**
	 * @return string
	 */
	public function getForeignTableName(): string
	{
		return $this->fkTableName;
	}

	public function getForeignColumns(): array
	{
		return $this->fkColumns;
	}

	public function onDelete(): ?string
	{
		return $this->onDelete;
	}

	public function onUpdate(): ?string
	{
		return $this->onUpdate;
	}

	/**
	 * @return mixed|null
	 */
	public function getDefault()
	{
		return $this->default;
	}

	protected function onEvent(string $name, array & $options)
	{
		if( ! isset($options[$name]) )
		{
			return;
		}

		$value = strtoupper($options[$name]);
		if( in_array($value, ["CASCADE", "SET NULL", "RESTRICT", "NO ACTION", "SET DEFAULT"], true) )
		{
			$this->{$name} = $value;
			if($value === "SET DEFAULT" && isset($options["default"]))
			{
				$this->default = $options["default"];
			}
		}
	}
}