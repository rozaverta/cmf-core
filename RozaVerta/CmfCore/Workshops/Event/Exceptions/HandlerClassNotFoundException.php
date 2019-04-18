<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 16.03.2019
 * Time: 15:52
 */

namespace RozaVerta\CmfCore\Workshops\Event\Exceptions;

use RozaVerta\CmfCore\Exceptions\ClassNotFoundException;
use RozaVerta\CmfCore\Workshops\Event\Interfaces\EventProcessorExceptionInterface;

class HandlerClassNotFoundException extends ClassNotFoundException implements EventProcessorExceptionInterface
{
}