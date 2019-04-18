<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 14:11
 */

namespace RozaVerta\CmfCore\Database\Query;

abstract class AbstractBuilderContainer
{
	/**
	 * @var Builder
	 */
	protected $builder;

	/**
	 * @var DbalQueryBuilder
	 */
	protected $dbalBuilder;

	public function __construct( Builder $builder )
	{
		$this->builder = $builder;
		$this->dbalBuilder = $builder->getDbalQueryBuilder();
	}

	public function makeClone( Builder $builder )
	{
		$container = clone $this;
		$container->builder = $builder;
		$container->dbalBuilder = $builder->getDbalQueryBuilder();
		return $container;
	}
}