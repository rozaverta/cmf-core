<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 29.07.2019
 * Time: 22:16
 */

namespace RozaVerta\CmfCore\Traits;

use RozaVerta\CmfCore\App;

/**
 * Class ServiceTrait
 *
 * @property array $services
 *
 * @package RozaVerta\CmfCore\Traits
 */
trait ServiceTrait
{
	/**
	 * Append services to this object
	 *
	 * @param mixed ...$args
	 *
	 * @throws \Throwable
	 */
	protected function thisServices( ... $args )
	{
		$count = count( $args );
		if( $count > 0 )
		{
			$this->thisServicesLoader( $count === 1 && is_array( $args[0] ) ? $args[0] : $args );
		}

		if( isset( $this->services ) && is_array( $this->services ) )
		{
			$this->thisServicesLoader( $this->services );
			$this->services = null;
		}
	}

	/**
	 * @param array $services
	 *
	 * @throws \Throwable
	 */
	private function thisServicesLoader( array $services )
	{
		foreach( $services as $service )
		{
			if( $service === "app" )
			{
				$this->app = App::getInstance();
			}
			else if( !isset( $this->{$service} ) )
			{
				$this->{$service} = self::service( $service );
			}
		}
	}

	/**
	 * Get root App class instance
	 *
	 * @return App
	 */
	public static function app(): App
	{
		static $app;

		if( !isset( $app ) )
		{
			$app = App::getInstance();
		}

		return $app;
	}

	/**
	 * Load singleton service object
	 *
	 * @param string $name
	 *
	 * @return object
	 *
	 * @throws \Throwable
	 */
	public static function service( string $name )
	{
		return self::app()->service( $name );
	}

	/**
	 * The Singleton class has been loaded
	 *
	 * @param string $name
	 * @param bool   $autoLoad
	 *
	 * @return bool
	 *
	 * @throws \Throwable
	 */
	public static function loaded( string $name, bool $autoLoad = false ): bool
	{
		return self::app()->loaded( $name, $autoLoad );
	}
}