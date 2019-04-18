<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 24.03.2019
 * Time: 13:49
 */

namespace RozaVerta\CmfCore\View\Exceptions;

use RozaVerta\CmfCore\Exceptions\NotFoundException;
use RozaVerta\CmfCore\View\Interfaces\PluginThrowableInterface;

class PluginNotFoundException extends NotFoundException implements PluginThrowableInterface
{
}