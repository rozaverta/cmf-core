<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2019
 * Time: 19:34
 */

namespace RozaVerta\CmfCore\Event\Exceptions;

use RozaVerta\CmfCore\Event\Interfaces\EventExceptionInterface;

class AccessException extends \RozaVerta\CmfCore\Exceptions\AccessException implements EventExceptionInterface
{
}