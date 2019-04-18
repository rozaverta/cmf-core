<?php
/**
 * Created by IntelliJ IDEA.
 * User: GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 13.03.2019
 * Time: 22:27
 */

namespace RozaVerta\CmfCore\Traits;

/**
 * Class ApplicationProxyTrait
 *
 * @property \RozaVerta\CmfCore\Log\LogManager $log
 * @property \RozaVerta\CmfCore\Helper\PhpExport $phpExport
 * @property \RozaVerta\CmfCore\Route\Url $url
 * @property \RozaVerta\CmfCore\View\View $view
 * @property \RozaVerta\CmfCore\Filesystem\Filesystem $filesystem
 * @property \RozaVerta\CmfCore\Language\LanguageManager $lang
 * @property \RozaVerta\CmfCore\Session\SessionManager $session
 * @property \RozaVerta\CmfCore\Database\DatabaseManager $database
 * @property \RozaVerta\CmfCore\Database\Connection $db
 * @property \RozaVerta\CmfCore\Controller\Controller $controller
 * @property \RozaVerta\CmfCore\Context\Context $context
 * @property \RozaVerta\CmfCore\Http\Response $response
 * @property \RozaVerta\CmfCore\Http\Request $request
 * @property \RozaVerta\CmfCore\Host\HostManager $host
 * @property \RozaVerta\CmfCore\Event\EventManager $event
 * @property \RozaVerta\CmfCore\Cache\CacheManager $cache
 *
 * @method string run()
 * @method \RozaVerta\CmfCore\Controller\Controller changeController( \RozaVerta\CmfCore\Controller\Controller $controller )
 * @method bool loadIs( string $name, bool $auto_load = false )
 * @method \RozaVerta\CmfCore\Context\Context loadContext()
 * @method object load( string $name )
 * @method \RozaVerta\CmfCore\App singleton( string $name, $object )
 * @method close()
 *
 * @package RozaVerta\CmfCore\Traits
 */
trait ApplicationProxyTrait
{
	use ApplicationTrait;

	public function __get( $name )
	{
		return $this->app->{$name};
	}

	public function __call( $name, $arguments )
	{
		return $this->app->{$name}( ... $arguments );
	}
}