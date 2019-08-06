<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 05.04.2016
 * Time: 18:32
 */

namespace RozaVerta\CmfCore\Event;

use Closure;
use InvalidArgumentException;
use RozaVerta\CmfCore\Event\Exceptions\EventOverloadException;
use RozaVerta\CmfCore\Event\Interfaces\EventInterface;
use RozaVerta\CmfCore\Support\Collection;

class Dispatcher
{
	/**
	 * Event name
	 *
	 * @type string
	 */
	private $name = '';

	/**
	 * Callbacks
	 *
	 * @type \SplPriorityQueue
	 */
	private $callbacks;

	/**
	 * Registered name
	 *
	 * @type array
	 */
	private $registered = [];

	private $complete = [];

	/**
	 * Is completable
	 *
	 * @var bool
	 */
	private $completable;

	/**
	 * Dispatcher in progress
	 *
	 * @type bool
	 */
	private $is_run = false;

	/**
	 * @var null|Closure
	 */
	private $preparatory = null;

	public function __construct( $name, $completable = false, Closure $preparatory = null )
	{
		$this->name = $name;
		$this->completable = (bool) $completable;
		$this->callbacks = new \SplPriorityQueue();
		$this->preparatory = $preparatory;
	}

	public function getName()
	{
		return $this->name;
	}

	public function count()
	{
		return $this->callbacks->count();
	}

	public function isRun()
	{
		return $this->is_run;
	}

	/**
	 * @param Closure $callback $callback
	 * @param int $priority
	 * @return $this
	 */
	public function listen( Closure $callback, $priority = 0)
	{
		$this->callbacks->insert( $callback, (int) $priority );
		return $this;
	}

	/**
	 * @param Closure $callback
	 * @param $name
	 * @return $this
	 */
	public function register( Closure $callback, $name)
	{
		if(! $this->isRegistered($name))
		{
			$this->registered[] = $name;
			$callback($this);
		}
		return $this;
	}

	public function isRegistered($name)
	{
		return in_array($name, $this->registered);
	}

	public function isCompletable()
	{
		return count($this->complete) > 0;
	}

	/**
	 * Dispatch events
	 *
	 * @param EventInterface $event
	 * @param Closure|null $callback
	 * @return Collection
	 */
	public function dispatch(EventInterface $event, Closure $callback = null)
	{
		if( $this->isRun() )
		{
			throw new EventOverloadException("The event '{$this->name}' is already running");
		}

		// clean data

		$this->complete = [];
		$dispatch = new Collection();
		$prepare = $this->preparatory;

		if( $prepare instanceof Closure )
		{
			$prepare($this);
			$this->preparatory = null;
		}

		if( ! $this->count() )
		{
			return $dispatch;
		}

		if( $event->getName() !== $this->name )
		{
			throw new InvalidArgumentException("The name of the Event does not match the name of the Dispatcher");
		}

		$isCall = $callback instanceof Closure;
		$this->is_run = true;

		foreach( clone $this->callbacks as $call )
		{
			if( $event->isPropagationStopped() )
			{
				break;
			}

			$result = $call($event);

			if( $this->completable && $result instanceof Closure )
			{
				$this->complete[] = $result;
				continue;
			}

			if( $isCall )
			{
				$result = $callback($result);
			}

			if( ! is_null($result) )
			{
				$dispatch[] = $result;
			}
		}

		$this->is_run = false;

		// clear all data if event was aborted
		if( $event->isPropagationStopped() )
		{
			$this->complete = [];
			$dispatch->reload();
		}

		return $dispatch;
	}

	public function complete(... $args)
	{
		foreach( $this->complete as $call )
		{
			$call(... $args);
		}
		$this->complete = [];
	}

	/**
	 * @return Dispatcher
	 */
	public function getCompletableClone()
	{
		$clone = new self($this->name, $this->completable);
		if($this->completable)
		{
			$clone->complete = $this->complete;
			$this->complete = [];
		}
		return $clone;
	}
}