<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:20
 */

namespace RozaVerta\CmfCore\Module\Exceptions;

use RozaVerta\CmfCore\Exceptions\RuntimeException;
use RozaVerta\CmfCore\Module\Interfaces\ModuleThrowableInterface;

class ModuleConflictVersionException extends RuntimeException implements ModuleThrowableInterface
{
}