<?php
declare(strict_types = 1);

// Current time
define("LIME_TIME", microtime(TRUE));

// Configuration
mb_internal_encoding('UTF-8');
ignore_user_abort(TRUE);

// Path to Lime
define('LIME_DIRECTORY', realpath( __DIR__.'/..'));

/**
 * Lime main configuration class
 *
 */
class Lime {

	/*
	 * Site URL (protocol + host + port)
	 */
	private static ?string $url = NULL;

	/*
	 * Site host
	 */
	private static ?string $host = NULL;

	/*
	 * Site domain
	 */
	private static ?string $domain = NULL;

	/*
	 * Site protocol
	 */
	private static ?string $protocol = NULL;

	/*
	 * Site port
	 */
	private static ?int $port = NULL;

	/*
	 * Site name
	 */
	private static ?string $name = NULL;

	/**
	 * Registered apps
	 *
	 * @var array
	 */
	private static array $apps = [];

	/**
	 * Sibling apps for internal builds
	 *
	 * @var array
	 */
	private static array $siblings = [];

	/**
	 * Define main app
	 *
	 * @param string $app
	 */
	public static function init(string $app): void {

		if(ctype_alpha($app) === FALSE) {
			trigger_error("App name must only contain alphabetic characters", E_USER_ERROR);
			exit;
		} else {
			define('LIME_APP', $app);
		}

	}

	/**
	 * Get the path for the given app
	 *
	 * @param string $app
	 */
	public static function getPath(string $app = LIME_APP): string {
		return LIME_DIRECTORY.'/'.$app;
	}


	/**
	 * Set apps
	 *
	 * @param array $apps
	 */
	public static function setApps(array $apps): void {
		self::$apps = $apps;
	}

	/**
	 * Get apps
	 *
	 * @return array
	 */
	public static function getApps(): array {
		return self::$apps;
	}

	/**
	 * Set siblings
	 *
	 * @param array $siblings
	 */
	public static function setSiblings(array $siblings): void {
		self::$siblings = $siblings;
	}

	/**
	 * Get siblings
	 *
	 * @return array
	 */
	public static function getSiblings(): array {
		return self::$siblings;
	}

	/**
	 * Set urls for each mode (dev, prod...) and select the right URL according to the current mode
	 *
	 * @param array $urls
	 */
	public static function setUrls(array $urls): void {

		if(isset($urls[LIME_ENV]) === FALSE) {
			trigger_error("No URL for mode '".LIME_ENV."'", E_USER_ERROR);
			exit;
		}

		self::$url = $urls[LIME_ENV];

		[
			'host' => self::$host,
			'domain' => self::$domain,
			'protocol' => self::$protocol,
			'port' => self::$port,
		] = analyze_url(self::$url);

	}

	/**
	 * Set site name
	 *
	 * @return array
	 */
	public static function setName(string $name): void {
		self::$name = $name;
	}

	/**
	 * Get site name
	 *
	 * @return array
	 */
	public static function getName(): ?string {
		return self::$name;
	}

	/**
	 * Get url
	 *
	 * @return array
	 */
	public static function getUrl(): ?string {
		return self::$url;
	}

	/**
	 * Get host from url
	 *
	 * @return array
	 */
	public static function getHost(): ?string {
		return self::$host;
	}

	/**
	 * Get domain from url
	 *
	 * @return array
	 */
	public static function getDomain(): ?string {
		return self::$domain;
	}

	/**
	 * Get protocol from url
	 *
	 * @return array
	 */
	public static function getProtocol(): ?string {
		return self::$protocol;
	}

	/**
	 * Get port from url
	 *
	 * @return array
	 */
	public static function getPort(): ?int {
		return self::$port;
	}

}

// Required files
require_once LIME_DIRECTORY.'/framework/core/Function.php';
require_once LIME_DIRECTORY.'/framework/core/Package.php';
require_once LIME_DIRECTORY.'/framework/core/Route.php';
require_once LIME_DIRECTORY.'/framework/core/Page.php';
require_once LIME_DIRECTORY.'/framework/core/Action.php';
require_once LIME_DIRECTORY.'/framework/core/Instruction.php';
require_once LIME_DIRECTORY.'/framework/core/Template.php';
require_once LIME_DIRECTORY.'/framework/core/View.php';
require_once LIME_DIRECTORY.'/framework/core/Module.php';
require_once LIME_DIRECTORY.'/framework/core/Data.php';

// Get instance depending http or cli access
if(server_exists('SERVER_NAME')) {
	(new HttpRoute())->run();
} else {
	(new CliRoute())->run();
}
?>
