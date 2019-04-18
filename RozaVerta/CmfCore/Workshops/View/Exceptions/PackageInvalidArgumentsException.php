<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.03.2019
 * Time: 2:08
 */

namespace RozaVerta\CmfCore\Workshops\View\Exceptions;

use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use RozaVerta\CmfCore\Interfaces\WorkshopProcessorThrowableInterface;

class PackageInvalidArgumentsException extends InvalidArgumentException implements WorkshopProcessorThrowableInterface
{
}