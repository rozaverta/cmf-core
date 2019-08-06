<?php
/**
 * Created by GoshaV [Maniako] <gosha@rozaverta.com>
 * Date: 12.03.2019
 * Time: 1:19
 */

namespace RozaVerta\CmfCore\Helper;

/**
 * Class Server
 * @package RozaVerta\CmfCore\Helper
 */
final class Server
{
	private function __construct()
	{
	}

	static public function isOsWindows(): bool
	{
		return strtolower(substr(PHP_OS, 0, 3)) === 'win';
	}

	/**
	 * Array of arguments passed to the script.
	 * When the script is run on the command line, this gives C-style access to the command line parameters.
	 * When called via the GET method, this will contain the query string.
	 *
	 * @return array
	 */
	static public function argv(): array
	{
		return $_SERVER["argv"] ?? [];
	}

	/**
	 * Contains the number of command line parameters passed to the script (if run on the command line).
	 *
	 * @return mixed
	 */
	static public function argc()
	{
		return $_SERVER["argc"] ?? null;
	}

	/**
	 * The filename of the currently executing script, relative to the document root.
	 *
	 * @return string
	 */
	static public function phpSelf(): string
	{
		return $_SERVER["PHP_SELF"] ?? "";
	}

	/**
	 * What revision of the CGI specification the server is using; i.e. 'CGI/1.1'.
	 *
	 * @return string
	 */
	static public function gatewayInterface(): string
	{
		return $_SERVER["GATEWAY_INTERFACE"] ?? "";
	}

	/**
	 * The IP address of the server under which the current script is executing.
	 *
	 * @return string
	 */
	static public function addr(): string
	{
		return $_SERVER["SERVER_ADDR"] ?? "";
	}

	/**
	 * The name of the server host under which the current script is executing.
	 * If the script is running on a virtual host, this will be the value defined for that virtual host.
	 *
	 * @return string
	 */
	static public function name(): string
	{
		return $_SERVER["SERVER_NAME"] ?? "";
	}

	/**
	 * Server identification string, given in the headers when responding to requests.
	 *
	 * @return string
	 */
	static public function software(): string
	{
		return $_SERVER["SERVER_SOFTWARE"] ?? "";
	}

	/**
	 * String containing the server version and virtual host name which are added to server-generated pages, if enabled.
	 *
	 * @return string
	 */
	static public function signature(): string
	{
		return $_SERVER["SERVER_SIGNATURE"] ?? "";
	}

	/**
	 * Name and revision of the information protocol via which the page was requested; i.e. 'HTTP/1.0';
	 *
	 * @return string
	 */
	static public function protocol(): string
	{
		return $_SERVER["SERVER_PROTOCOL"] ?? "";
	}

	/**
	 * The document root directory under which the current script is executing, as defined in the server's configuration file.
	 *
	 * @return string
	 */
	static public function documentRoot(): string
	{
		return $_SERVER["DOCUMENT_ROOT"] ?? "";
	}

	/**
	 * The query string, if any, via which the page was accessed.
	 *
	 * @return string
	 */
	static public function queryString(): string
	{
		return $_SERVER["QUERY_STRING"] ?? "";
	}

	/**
	 * The absolute pathname of the currently executing script.
	 *
	 * @return string
	 */
	static public function scriptFilename(): string
	{
		return $_SERVER["SCRIPT_FILENAME"] ?? "";
	}

	/**
	 * Contains the current script's path. This is useful for pages which need to point to themselves.
	 * The __FILE__ constant contains the full path and filename of the current (i.e. included) file.
	 *
	 * @return string
	 */
	static public function scriptName(): string
	{
		return $_SERVER["SCRIPT_NAME"] ?? "";
	}

	/**
	 * Filesystem- (not document root-) based path to the current script, after the server has done any virtual-to-real mapping.
	 *
	 * @return string
	 */
	static public function pathTranslated(): string
	{
		return $_SERVER["PATH_TRANSLATED"] ?? "";
	}

	/**
	 * Contains any client-provided pathname information trailing the actual script filename but preceding the query string, if available.
	 *
	 * @return string
	 */
	static public function pathInfo(): string
	{
		return $_SERVER["PATH_INFO"] ?? "";
	}

	/**
	 * Original version of 'PATH_INFO' before processed by PHP.
	 *
	 * @return string
	 */
	static public function origPathInfo(): string
	{
		return $_SERVER["ORIG_PATH_INFO"] ?? "";
	}

	/**
	 * HTTP - accept, acceptCharset, acceptEncoding, acceptLanguage, connection, host, referer, userAgent
	 *
	 * @param string $name
	 * @return string|null
	 */
	static public function http(string $name)
	{
		$name = "HTTP_" . Str::upper(Str::snake($name));
		return $_SERVER[$name] ?? null;
	}

	/**
	 * REMOTE - addr, host, port, user, redirectUser
	 * @param string $name
	 * @return null
	 */
	static public function remote(string $name)
	{
		$name = Str::upper(Str::snake($name));
		return $_SERVER[$name === "REDIRECT_USER" ? "REDIRECT_REMOTE_USER" : ("REDIRECT_" . $name)] ?? null;
	}

	/**
	 * REQUEST - method, time, timeFloat
	 * @param string $name
	 * @return null
	 */
	static public function request(string $name)
	{
		$name = "REQUEST_" . Str::upper(Str::snake($name));
		return $_SERVER[$name] ?? null;
	}

	/**
	 * PHP AUTH - type, digest, user, pw
	 *
	 * @param string $name
	 * @return mixed
	 */
	static public function auth(string $name)
	{
		$name = Str::upper(Str::snake($name));
		return $_SERVER[ $name === "TYPE" ? "AUTH_TYPE" : ("PHP_AUTH_" . $name) ] ?? null;
	}
}