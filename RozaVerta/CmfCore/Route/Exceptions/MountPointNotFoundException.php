<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 20.03.2019
 * Time: 10:22
 */

namespace RozaVerta\CmfCore\Route\Exceptions;

use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\Route\Interfaces\RouteThrowableInterface;

class MountPointNotFoundException extends NotFoundException implements RouteThrowableInterface
{

}