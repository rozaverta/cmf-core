<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 31.07.2019
 * Time: 4:13
 */

namespace RozaVerta\CmfCore\Route\Interfaces;

/**
 * Interface ControllerTemplateInterface
 *
 * @package RozaVerta\CmfCore\Route\Interfaces
 */
interface ControllerTemplateInterface
{
	/**
	 * Get template name
	 *
	 * @return string|null
	 */
	public function getTemplateName(): ?string;
}