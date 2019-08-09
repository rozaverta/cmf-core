<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 08.08.2019
 * Time: 15:12
 */

namespace RozaVerta\CmfCore\Workshops\View\Exceptions;

use RozaVerta\CmfCore\Exceptions\RuntimeException;
use RozaVerta\CmfCore\Interfaces\WorkshopProcessorThrowableInterface;

class FilesystemException extends RuntimeException implements WorkshopProcessorThrowableInterface
{
}