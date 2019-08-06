<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 23.09.2017
 * Time: 5:33
 */

namespace RozaVerta\CmfCore\Events;

use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Event\Event;
use RozaVerta\CmfCore\Interfaces\WorkshopInterface;

/**
 * Class WorkshopEvent
 *
 * @property \RozaVerta\CmfCore\App $app
 *
 * @package RozaVerta\CmfCore\Events
 */
abstract class WorkshopEvent extends Event
{
	/**
	 * @var WorkshopInterface
	 */
	protected $processor;

	/**
	 * @var string
	 */
	protected $processName;

	/**
	 * WorkshopEvent constructor.
	 *
	 * @param WorkshopInterface $workshop
	 * @param string $processName
	 * @param array $params
	 */
	public function __construct( WorkshopInterface $workshop, string $processName, array $params = [] )
	{
		$params["app"] = App::getInstance();
		parent::__construct($params);
		$this->processor = $workshop;
		$this->processName = $processName;
	}

	/**
	 * @return WorkshopInterface
	 */
	public function getProcessor(): WorkshopInterface
	{
		return $this->processor;
	}

	/**
	 * @return string
	 */
	public function getProcessName(): string
	{
		return $this->processName;
	}
}