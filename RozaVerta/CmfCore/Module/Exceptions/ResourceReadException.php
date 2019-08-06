<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 15.03.2019
 * Time: 23:20
 */

namespace RozaVerta\CmfCore\Module\Exceptions;

use RozaVerta\CmfCore\Filesystem\Exceptions\FileReadException;
use RozaVerta\CmfCore\Module\Interfaces\ModuleThrowableInterface;

class ResourceReadException extends FileReadException implements ModuleThrowableInterface
{
}