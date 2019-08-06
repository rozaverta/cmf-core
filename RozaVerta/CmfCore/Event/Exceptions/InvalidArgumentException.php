<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2019
 * Time: 19:34
 */

namespace RozaVerta\CmfCore\Event\Exceptions;

use RozaVerta\CmfCore\Event\Interfaces\EventExceptionInterface;

class InvalidArgumentException extends \RozaVerta\CmfCore\Exceptions\InvalidArgumentException implements EventExceptionInterface
{

}