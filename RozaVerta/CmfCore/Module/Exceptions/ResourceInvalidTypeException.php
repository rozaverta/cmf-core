<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:20
 */

namespace RozaVerta\CmfCore\Module\Exceptions;

use RozaVerta\CmfCore\Exceptions\InvalidArgumentException;
use RozaVerta\CmfCore\Module\Interfaces\ModuleThrowableInterface;

class ResourceInvalidTypeException extends InvalidArgumentException implements ModuleThrowableInterface
{
}