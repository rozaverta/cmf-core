<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 03.04.2018
 * Time: 0:22
 */

namespace RozaVerta\CmfCore\Interfaces;

use RozaVerta\CmfCore\Log\Interfaces\LoggableInterface;
use RozaVerta\CmfCore\Module\Interfaces\ModuleGetterInterface;

interface WorkshopInterface extends ModuleGetterInterface, LoggableInterface
{
}