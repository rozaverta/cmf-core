<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 11.03.2019
 * Time: 17:41
 */

namespace RozaVerta\CmfCore\Database\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;

class DbalQueryBuilder extends QueryBuilder
{
	protected $_boundCounter = 1;

	protected $_boundPlaceholderPrefix = ":dcValue";

	protected static $boundPlaceholderCounter = 1;

	public function __construct( Connection $connection )
	{
		parent::__construct( $connection );
		$this->_boundPlaceholderPrefix .= ( self::$boundPlaceholderCounter ++ ) . "n";
	}

	public function createNamedParameter($value, $type = ParameterType::STRING, $placeHolder = null)
	{
		if( $placeHolder === null )
		{
			$placeHolder = $this->_boundPlaceholderPrefix . ( $this->_boundCounter ++ );
		}

		return parent::createNamedParameter($value, $type, $placeHolder);
	}
}