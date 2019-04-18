<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.08.2018
 * Time: 12:29
 */

namespace RozaVerta\CmfCore\View\Events;

use RozaVerta\CmfCore\View\Template;
use RozaVerta\CmfCore\View\View;

/**
 * Class PreRenderEvent
 *
 * @property-read Template $template
 * @property-read bool $fromCache
 *
 * @package RozaVerta\CmfCore\View\Events
 */
class PreRenderEvent extends RenderEvent
{
	public function __construct( View $view, Template $template, bool $fromCache )
	{
		parent::__construct( $view, compact('template', 'fromCache') );
	}
}