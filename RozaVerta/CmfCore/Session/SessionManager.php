<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 01.11.2015
 * Time: 22:17
 */

namespace RozaVerta\CmfCore\Session;

use RozaVerta\CmfCore\Interfaces\Getter;
use RozaVerta\CmfCore\Support\Prop;
use RozaVerta\CmfCore\Traits\ComparatorTrait;
use RozaVerta\CmfCore\Traits\GetTrait;
use RozaVerta\CmfCore\Traits\SingletonInstanceTrait;

/**
 * Class SessionManager
 *
 * @method static SessionManager getInstance()
 *
 * @package RozaVerta\CmfCore\Session
 */
class SessionManager implements Getter
{
	use SingletonInstanceTrait;
	use GetTrait;
	use ComparatorTrait;

	protected $items = [];

	private $id = '';

	public function __construct()
	{
		if( ! isset($_SESSION) || ! session_id() )
		{
			self::open();
		}

		$this->update();
	}

	public static function open( Prop $prop = null, $override = false )
	{
		if( $override || ! isset($_SESSION) || ! session_id() )
		{
			if(session_id())
			{
				session_write_close();
			}

			if( is_null($prop) )
			{
				$prop = Prop::prop('session');
			}

			$hnr = false;

			if( $prop->has( 'handler' ) )
			{
				$handler = $prop['handler'];
				if( is_string($handler) && class_exists($prop['handler'], true) )
				{
					$handler = new $handler();
				}

				if( $handler instanceof \SessionHandlerInterface )
				{
					$hnr = session_set_save_handler( $handler, ! $prop->equiv('register_shutdown', false) );
				}

				if( ! $hnr )
				{
					throw new \Exception("Can't create new session");
				}
			}

			if( $prop->has( 'session_name' ) )
			{
				session_name($prop['session_name']);
			}

			if( ! $hnr )
			{
				session_start($prop->getArray('options'));
			}
			else
			{
				session_start();
			}

			if( self::hasInstance() )
			{
				self::getInstance()->update();
			}
		}
	}

	public function __destruct()
	{
		if( $this->id )
		{
			session_write_close();
			$this->close();
		}
	}

	public function destroy()
	{
		if( $this->id )
		{
			session_destroy();
			$this->close();
		}
	}

	public function set( $name, $value )
	{
		$this->items[$name] = $value;
		return $this;
	}

	public function setNull( $name )
	{
		unset( $this->items[$name] );
		return $this;
	}

	public function setNullMask( $mask )
	{
		foreach( array_keys($this->items) as $key )
		{
			if( preg_match($mask, $key) )
			{
				unset($this->items[$key]);
			}
		}
		return $this;
	}

	public function setNullForAll()
	{
		$_SESSION = [];
		return $this;
	}

	public function setData( array $data )
	{
		foreach( $data as $key => $value )
		{
			$this->items[$key] = $value;
		}
		return $this;
	}

	protected function close()
	{
		$this->id = false;
		$this->items = [];
		unset( $_SESSION );
	}

	protected function update()
	{
		unset($this->items);
		$this->items = & $_SESSION;
		$this->id = session_id();
	}
}