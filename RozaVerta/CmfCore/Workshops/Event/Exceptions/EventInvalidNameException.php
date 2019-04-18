<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 15:52
 */

namespace RozaVerta\CmfCore\Workshops\Event\Exceptions;

use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use RozaVerta\CmfCore\Workshops\Event\Interfaces\EventProcessorExceptionInterface;

class EventInvalidNameException extends InvalidArgumentException implements EventProcessorExceptionInterface
{
}