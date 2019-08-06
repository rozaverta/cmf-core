<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.09.2017
 * Time: 5:47
 */

namespace RozaVerta\CmfCore\Event\Exceptions;

use RozaVerta\CmfCore\Event\Interfaces\EventExceptionInterface;
use RozaVerta\CmfCore\Exceptions\RuntimeException;

class EventAbortException extends RuntimeException implements EventExceptionInterface
{
}