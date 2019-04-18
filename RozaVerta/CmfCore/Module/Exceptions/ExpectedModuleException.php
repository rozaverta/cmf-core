<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 18.04.2019
 * Time: 8:08
 */

namespace RozaVerta\CmfCore\Module\Exceptions;

use RozaVerta\CmfCore\Exceptions\RuntimeException;
use RozaVerta\CmfCore\Module\Interfaces\ModuleThrowableInterface;

class ExpectedModuleException extends RuntimeException implements ModuleThrowableInterface
{

}