<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 19:41
 */

namespace RozaVerta\CmfCore\Database\Scheme;


class Index extends AbstractKey
{
	protected $type;

	public function __construct(?string $name, array $columns, string $type = "INDEX")
	{
		$this->name = $name;
		$this->columns = $columns;
		$this->type = strtoupper($type);
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return bool
	 */
	public function isPrimary(): bool
	{
		return $this->type === "PRIMARY";
	}

	/**
	 * @return bool
	 */
	public function isUnique(): bool
	{
		return $this->isPrimary() || $this->type === "UNIQUE";
	}
}