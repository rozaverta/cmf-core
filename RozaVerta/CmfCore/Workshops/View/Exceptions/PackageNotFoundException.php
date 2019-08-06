<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.03.2019
 * Time: 2:08
 */

namespace RozaVerta\CmfCore\Workshops\View\Exceptions;

use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Interfaces\WorkshopProcessorThrowableInterface;

class PackageNotFoundException extends NotFoundException implements WorkshopProcessorThrowableInterface
{
}