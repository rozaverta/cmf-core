<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 09.03.2019
 * Time: 12:39
 */

defined( "APP_BASE_PATH" ) || die( "\"APP_BASE_PATH\" is not defined." );

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Exception\DriverException;
use RozaVerta\CmfCore\App;
use RozaVerta\CmfCore\Database\DatabaseManager;
use RozaVerta\CmfCore\Events\ThrowableEvent;
use RozaVerta\CmfCore\Helper\Path;
use RozaVerta\CmfCore\Route\Exceptions\PageNotFoundException;

// base constants

define("APP_CORE_PATH", __DIR__ . DIRECTORY_SEPARATOR );

defined("CMF_CORE")         || define("CMF_CORE"        , true);
defined("NOW_MICROTIME")    || define("NOW_MICROTIME"   , microtime(true));
defined("NOW_TIME")         || define("NOW_TIME"        , time());
defined("SERVER_CLI_MODE")  || define("SERVER_CLI_MODE" , function_exists("php_sapi_name") && php_sapi_name() === 'cli');

// Обработка ошибок

set_exception_handler(static function( Throwable $exception )
{
	// fix recursive
	static $run = false;
	if( $run )
	{
		return;
	}

	$run = true;
	$isError = $exception instanceof Error;

	// if there is something there clear the data buffer

	if( function_exists('ob_get_level') )
	{
		$ob_level = ob_get_level();
		while( $ob_level -- > 0 )
		{
			ob_end_clean();
		}
	}

	function exc_print( $text, array $args = [])
	{
		if( SERVER_CLI_MODE )
		{
			$text .= "\n";
		}
		else if( ! headers_sent() )
		{
			header("Content-Type: text/plain; charset=utf-8");
		}

		vprintf($text, $args);
		exit;
	}

	function exc_db($exception)
	{
		if( $exception instanceof DBALException )
		{
			$app = App::getInstance();
			$app->log->line("SQL error: " . $exception->getMessage());
			if( $exception instanceof DriverException )
			{
				$app->log->line("SQL error query [" . $exception->getErrorCode() . "]: " . $exception->getSQLState());
			}

			foreach( DatabaseManager::getInstance()->getActiveConnections() as $conn)
			{
				if( $conn->isTransactionActive() )
				{
					$conn->rollBack();
				}
			}
		}
	}

	$app = class_exists( App::class, false ) ? App::getInstance() : null;
	if( !( $app && $app->initialized() && $app->installed() ) )
	{
		exc_print( "%s. %s, %s", [ ( $isError ? "Fatal error" : "System error" ), $exception->getCode(), $exception->getMessage() ] );
	}

	$title    = $isError ? "Fatal error" : "System error";
	$code     = $exception->getCode();
	$codeName = $exception instanceof \RozaVerta\CmfCore\Interfaces\ThrowableInterface ? $exception->getCodeName() : null;
	$message  = $exception->getMessage();
	$output   = false;
	$is_send  = false;
	$response = $app->response;
	$page404  = $exception instanceof PageNotFoundException;

	// header code

	if( ! $is_send )
	{
		$response
			->header("Content-Type", "text/html; charset=utf-8")
			->setBody('');

		if( $code >= 200 && $code <= 505 )
		{
			$response->setCode($code);
		}
		else if($page404)
		{
			$response->setCode(404);
		}
	}

	$is_send = $response->isSent() || $response->isLocked();

	// write an error to the log file

	$app->log->throwable($exception);

	// database error

	exc_db($exception);

	// event

	try {
		$app->event->dispatch( new ThrowableEvent($exception),
			function( $result ) use ( & $output ) {
				if( $result instanceof Closure )
				{
					$output = $result;
					return false;
				}
				else
				{
					return null;
				}
			});
	}
	catch(DBALException $e) {
		exc_db($e);
	}
	catch(\RozaVerta\CmfCore\Exceptions\WriteException $e) {}

	try {
		$app->close();
	}
	catch(DBALException $e) {
		exc_db($e);
	}
	catch(\RozaVerta\CmfCore\Exceptions\WriteException $e) {}

	if( $output instanceof Closure )
	{
		$output();
		$is_send = true;
	}

	if( $is_send )
	{
		exit;
	}

	if( SERVER_CLI_MODE )
	{
		$response->setBody( sprintf( "\033[31;31m%s\033[0m", $title . ( $code ? " [{$code}]:" : ':' ) ) . " " . $message . PHP_EOL );
	}
	else
	{
		$body = null;

		// 404 page from file
		if( $page404 )
		{
			$file = Path::application('404_error.html');
			if( file_exists($file) ) $body = @ file_get_contents($file);
		}

		if( $page404 )
		{
			$title = $message;
			$headTitle = $code;
		}
		else
		{
			$headTitle = $title;
			if( $codeName )
			{
				$headTitle .= " [{$codeName}]";
			}
			else if( $code )
			{
				$headTitle .= " [{$code}]";
			}
		}

		$mode = defined( "APP_DEBUG_MODE" ) ? APP_DEBUG_MODE : "production";
		$debug = "";

		// default error page from file
		if( !$body )
		{
			$file = Path::application( 'system_error.inc.php' );
			if( file_exists($file) )
			{
				$compact = compact('title', 'code', 'codeName', 'headTitle', 'message', 'mode', 'isError', 'replace');
				$compact['charset'] = 'utf-8';
				ob_start();
				Path::includeFile($file, $compact);
				$body = ob_get_contents();
				ob_end_clean();
			}
			else if($mode === "development" && (! $page404 || $code !== 404))
			{
				$debug = get_class( $exception ) . ", trace: \n" . $exception->getTraceAsString();
				if( SERVER_CLI_MODE )
				{
					$debug = "<pre>{$debug}</pre>";
				}
			}
		}

		if( ! $body )
		{
			if( SERVER_CLI_MODE )
			{
				$body = "Title: {$title}\nCode: {$codeName}\nMessage: {$message}";
				if( $debug )
				{
					$body .= "\n{$debug}";
				}
			}
			else
			{
				$body = <<<EOT
<!DOCTYPE html>
<html>
<head>
	<title>{$title}</title>
	<meta charset="utf-8" />
	<meta http-equiv="Content-Type" content="text-html; charset=utf-8" />
	<link href="https://fonts.googleapis.com/css?family=Roboto:400,300&subset=latin,cyrillic" rel="stylesheet" type="text/css" />
	<style type="text/css">html{background-color:#f3f3f3}body{font:300 14px Roboto,Verdana,Arial,sans-serif;margin:0;padding:0;color:#333}.center{background-color:white;margin:120px auto;position:relative;width:440px;padding-bottom:20px;border-radius:2px;-webkit-box-shadow:0 1px 3px rgba(0,0,0,0.2);box-shadow:0 1px 3px rgba(0,0,0,0.2)}.center pre{margin:10px 20px;border:1px solid #ccc;padding:10px;overflow:auto}.center.mode-development{width:800px}h1{font-weight:400;font-size:18px;color:#000;padding:20px 20px 18px;margin:1px 1px 30px;border-bottom:1px solid #eee}p{margin:10px 20px}</style>
</head>
<body>
<div class="center mode-{$mode}">
	<h1>{$headTitle}</h1>
	<p>{$message}</p>
	{$debug}
</div>
</body>
</html>
EOT;
			}
		}

		$response->setBody($body);
	}

	$send = false;

	try {
		$response->send(true);
		$send = true;
	}
	catch(DBALException $e)
	{
		exc_db($e);
	}
	catch(\RozaVerta\CmfCore\Exceptions\WriteException $e) {}

	$send || exc_print((SERVER_CLI_MODE ? "\033[31;31m%s (%s)\033[0m" : "%s (%s)") . ": %s", [$title, $codeName ?? $code, $message]);

	exit;
});
