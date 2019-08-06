<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace RozaVerta\CmfCore\View\Events;

use RozaVerta\CmfCore\View\Template;
use RozaVerta\CmfCore\View\View;

/**
 * Class CompleteRenderEvent
 *
 * @property-read Template $template
 * @property string $body
 *
 * @package RozaVerta\CmfCore\Events
 */
class CompleteRenderEvent extends RenderEvent
{
	public function __construct( View $view, Template $template, string $body )
	{
		parent::__construct($view, compact('template', 'body'));
		$this->setAllowed("body");
	}
}