<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace RozaVerta\CmfCore\Events;

use RozaVerta\CmfCore\Context\Context;
use RozaVerta\CmfCore\Support\Collection;

/**
 * Class ContextEvent
 *
 * @property Context context
 * @property Collection collection
 *
 * @package RozaVerta\CmfCore\Events
 */
class ContextEvent extends SystemEvent
{
	public function __construct( Context $context, Collection $collection )
	{
		parent::__construct(compact('context', 'collection'));
		$this->setAllowed("context", Context::class);
	}
}