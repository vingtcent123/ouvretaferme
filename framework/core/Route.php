<?php

/**
 * Generic access class
 */
abstract class Route {

	use Notifiable;


	/**
	 * All registered routes
	 */
	private static array $routes = [];

	/**
	 * Request build by constructor
	 */
	protected ?string $request;

	/**
	 * Requested with?
	 */
	protected static ?string $requestedWith = NULL;

	/**
	 * Origin of the request
	 */
	protected static ?string $requestedOrigin = NULL;

	/**
	 * Force context
	 */
	protected static ?string $requestedContext = 'new';

	/**
	 * Request method (GET, POST...)
	 */
	protected static ?string $requestMethod = NULL;

	/**
	 * Register a list of routes
	 *
	 * @param array $routes
	 */
	public function register(array $routes) {
		self::$routes += $routes;
	}

	/**
	 * Returns selected page class
	 *
	 * @return string Class name
	 */
	public function run() {

		// Clean inputs
		array_utf8($_GET);
		array_utf8($_POST);
		array_utf8($_REQUEST);

		// Handle fatal errors
		$this->handleErrors();

		// Build compatible pages from the request
		Page::run($this->request, TRUE);

	}

	public static function getCompatiblePages(string $request, bool $redirect = FALSE): array {

		// Look for compatible route
		$requestChunk = explode('/', rtrim($request, '/'));
		$requestSize = count($requestChunk);
		$requestSlash = (substr($request, -1) === '/');

		$pages = [];

		$routes = self::$routes[Route::getRequestMethod()] ?? [];

		foreach($routes as $name => $route) {

			$routeSize = count($route['route']);
			$routeSlash = (substr($name, -1) === '/');

			if($routeSize !== $requestSize) {
				continue;
			}

			$requestCompatible = TRUE;
			$requestInput = [];

			for($i = 0; $i < $routeSize; $i++) {

				$routePart = $route['route'][$i];
				$requestPart = $requestChunk[$i];

				$trailingRouteIndex = strpos($routePart, ':');
				$trailingRequestIndex = strpos($requestPart, ':');
				$trailingRoutePart = '';
				$trailingRequestPart = '';

				if($trailingRouteIndex !== FALSE) {
					$trailingRoutePart = substr($routePart, $trailingRouteIndex);
				}
				if($trailingRequestIndex !== FALSE) {
					$trailingRequestPart = substr($requestPart, $trailingRequestIndex);
				}

				if($trailingRequestPart !== $trailingRoutePart) {
					$requestCompatible = FALSE;
					break;
				}

				if($routePart !== '' and $routePart[0] === '{') {

					if($trailingRequestIndex !== FALSE) {
						$key = substr($routePart, 1, strpos($routePart, '}') - 1);
						$value = substr($requestPart, 0, -strlen($trailingRequestPart));
					} else {
						$key = substr($routePart, 1, -1);
						$value = $requestPart;
					}

					if(strpos($key, '@') !== FALSE) {

						[$key, $cast] = explode('@', $key, 2);

						$requestCompatible = Filter::check($cast, $value);

					} else {
						$requestCompatible = TRUE;
					}

					$requestInput[$key] = $value;

				} else {
					$requestCompatible = ($routePart === $requestPart);
				}

				if($requestCompatible === FALSE) {
					break;
				}

			}

			if($requestCompatible === TRUE) {

				$priority = $route['priority'];

				if($requestSlash !== $routeSlash and $redirect) {
;
					if($requestSlash) {
						$rewrite = rtrim(LIME_REQUEST_PATH, '/').LIME_REQUEST_ARGS;
					} else {
						$rewrite = LIME_REQUEST_PATH.'/'.LIME_REQUEST_ARGS;
					}

					$priority += 100;

				} else {
					$rewrite = NULL;
				}

				$pages[$priority] = [
					$route['request'],
					$name,
					$requestInput,
					$rewrite
				];

			}

		}

		return $pages;

	}

	public static function getRequestedWith(): ?string {
		return self::$requestedWith;
	}

	public static function getRequestedOrigin(): ?string {
		return self::$requestedOrigin;
	}

	public static function getRequestedContext(): string {
		return self::$requestedContext;
	}

	public static function getRequestMethod(): string {
		return self::$requestMethod;
	}

	/**
	 * Load lime.c.php file
	 */
	protected function loadConf(string $request) {

		require Lime::getPath().'/lime.c.php';

		if(LIME_ENV === 'dev') {

			require_once Lime::getPath('framework').'/dev/lib/Package.l.php';

			try {

				$libPackage = new dev\PackageLib($request);
				$libPackage->buildPackage();
				$libPackage->buildRoute();

			} catch(Exception $e) {

			}

		}

		require_once Lime::getPath().'/package.c.php';
		require_once Lime::getPath().'/route.c.php';

		if(LIME_ENV === 'dev') {
			self::notify('loadConf');
		}

	}

	protected function handleErrors() {

		// Start error handler
		set_error_handler(function($code, $message, $file, $line) {
			dev\ErrorPhpLib::handle(dev\Error::PHP, $code, $message, $file, $line);
			error_clear_last();
		});

		// Handle exceptions
		set_exception_handler(function($e) {

			dev\ErrorPhpLib::handle($e);
			ModuleModel::rollBackEverything();

			(new StatusAction(500))->run();

		});

		// Shutdown function for errors
		register_shutdown_function(function() {

			$lastError = error_get_last();

			if($lastError) {
				dev\ErrorPhpLib::handle(dev\Error::PHP, $lastError['type'], $lastError['message'], $lastError['file'], $lastError['line']);
			}

		});

		// Shutdown function that send default content type if no action has been thrown
		register_shutdown_function(function() {

			if(Action::ran() === 0) {
				(new VoidAction())->run();
			}

		});

	}

}

/**
 * Run lime in http mode
 */
class HttpRoute extends Route {

	/**
	 * Build environment
	 */
	public function __construct() {

		ob_start();

		// Get env
		$env = GET('limeEnv', '?string');

		if($env === NULL) {
			throw new Exception('Lime env is not defined');
		}

		unset($_GET['limeEnv'], $_REQUEST['limeEnv']);
		define('LIME_ENV', $env);

		// Get access
		if(
			LIME_ENV === 'dev' and
			get_exists('limeAccess')
		) {

			self::$requestedWith = GET('limeAccess');

		} else {

			if(server_exists('HTTP_X_REQUESTED_WITH')) {

				self::$requestedWith = SERVER('HTTP_X_REQUESTED_WITH');

				if(self::$requestedWith === 'ajax') {
					self::$requestedOrigin = SERVER('HTTP_X_REQUESTED_ORIGIN', '?string');
				}

				self::$requestedContext = SERVER('HTTP_X_REQUESTED_CONTEXT', ['new', 'reuse'], 'new');

			} else {
				self::$requestedWith = 'http';
			}

		}

		// Defines current host
		define("LIME_HOST", $_SERVER['HTTP_HOST'] ?? Lime::getHost());

		// Get app
		$app = GET('limeApp', '?string');
		$request = GET('limeName');
		$request = ltrim($request, '/');

		str_utf8($request);

		define('LIME_PAGE_REQUESTED', $request);

		Lime::init($app);

		// Get required configuration files
		$this->loadConf($request);

		// Clean GET and REQUEST arrays
		unset($_GET['limeName'], $_REQUEST['limeName']);
		unset($_GET['limeMode'], $_REQUEST['limeMode']);
		unset($_GET['limeApp'], $_REQUEST['limeApp']);

		// Save request
		$limeRequest = SERVER('REQUEST_URI');

		if(str_contains($limeRequest, '?')) {
			$position = strpos($limeRequest, '?');
			$limeRequestArgs = substr($limeRequest, $position);
			$limeRequestPath = substr($limeRequest, 0, $position);
		} else {
			$limeRequestArgs = '';
			$limeRequestPath = $limeRequest;
		}

		define('LIME_URL', Lime::getProtocol().'://'.LIME_HOST.$limeRequest);
		define('LIME_REQUEST', $limeRequest);
		define('LIME_REQUEST_PATH', $limeRequestPath);
		define('LIME_REQUEST_ARGS', $limeRequestArgs);

		// Save request method
		self::$requestMethod = SERVER('REQUEST_METHOD', 'string', 'GET');

		if(in_array(Route::getRequestMethod(), ['GET', 'POST', 'DELETE', 'HEAD', 'PUT']) === FALSE) {
			header("HTTP/1.0 405 Method Not Allowed");
			exit;
		}

		$this->request = $request;

	}

}

/**
 * Run lime in cli mode
 *
 */
class CliRoute extends Route {

	/**
	 * Build environment
	 */
	public function __construct() {

		self::$requestedWith = 'cli';

		/*
		 * Get all arguments
		 */
		$args = array_slice(SERVER('argv', 'array'), 1);

		// Defines current host
		define("LIME_HOST", NULL);

		if(empty($args)) {
			$this->getHelp();
		}

		$param = NULL;
		$run = NULL;

		$_SERVER['constants'] = [];

		foreach($args as $position => $arg) {

			if($param !== NULL) {

				switch($param) {

					case 'app' :
						Lime::init($arg);
						break;

					case 'env' :
						define('LIME_ENV', $arg);
						break;

					case 'constant' :
						$constant = $this->getArgument($arg, FALSE);
						if($constant !== NULL) {
							foreach($constant as $name => $value) {
								define($name, $value);
								$_SERVER['constants'][$name] = $value;
							}
						} else {
							$this->getHelp();
						}
						break;

					default :
						break(2);

				}

				$param = NULL;

			} else {

				switch($arg) {

					case '-a' :
						$param = 'app';
						break;

					case '-e' :
						$param = 'env';
						break;

					case '-c' :
						$param = 'constant';
						break;

					case '-r' :
						$position++;
						$run = $args[$position];
						break(2);

					default :
						break(2);

				}

			}

		}

		if(defined('LIME_APP') === FALSE) {
			echo "Error: App is missing\n";
			exit;
		}

		if(defined('LIME_ENV') === FALSE) {
			echo "Error: Env is missing\n";
			exit;
		}

		$this->extractArguments($args, $position);

		// Save request
		$request = $args[$position];

		str_utf8($request);

		define('LIME_PAGE_REQUESTED', $request);

		define('LIME_REQUEST_PATH', '/'.$request);
		define('LIME_REQUEST_ARGS', ($_GET ? '?'.http_build_query($_GET) : ''));
		define('LIME_REQUEST', LIME_REQUEST_PATH.LIME_REQUEST_ARGS);

		// Save request method
		self::$requestMethod = 'GET';

		// Get required configuration files
		$this->loadConf($request);

		$this->request = $request;

		if($run) {

			eval('class Php {
				public function run() {
					'.$run.'
				}
			}');

			if(class_exists('Php', FALSE)) {

				Page::doInit('cli');

				$object = new Php;
				$object->run();
				exit;

			} else {
				echo "Error: Your PHP code is invalid\n";
				exit;
			}

		}

	}


	/**
	 * Extract get and request arguments
	 *
	 * @param array $args
	 * @param int $position
	 */
	private function extractArguments(array $args, int $position) {

		$args = array_slice($args, $position + 1);

		foreach($args as $arg) {

			if($arg === "") {
				continue;
			}

			$argument = $this->getArgument($arg, TRUE);

			if($argument !== NULL) {
				$_GET = array_merge_recursive($_GET, $argument);
				$_REQUEST = array_merge_recursive($_REQUEST, $argument);
			} else {
				echo "Error: Page arguments must have the following syntax: name=value ('".$arg."' found)\n";
				exit;
			}

		}

	}


	/**
	 * Get argument name and value
	 */
	private function getArgument(string $string, bool $withArrays): ?array {

		if(preg_match("/^([a-z0-9\_\-".($withArrays ? '\\[\\]' : '')."]+)=(.*)$/si", $string, $match) ) {

			$name = $match[1];
			$value = $match[2];

			$first = substr($value, 0, 1);
			$last = substr($value, -1);

			if(
				$first === $last and
				($first === '"' or $first === '\'')
			) {

				$value = substr($value, 1, -1);
				$value = stripcslashes($value);

			}

			if(strpos($name, '[')) {

				if(preg_match('/^([a-z0-9\_\-]+)(\[[a-z0-9\_\-]+\])+$/si', $name, $list) > 0) {

					$names = explode('[', str_replace(']', '', $name));

					$arg = [];
					$currentArg = &$arg;

					foreach(array_slice($names, 0, -1) as $name) {
						$currentArg[$name] = [];
						$currentArg = &$currentArg[$name];
					}

					$currentArg[last($names)] = $value;

					return $arg;

				} else {
					echo "Error: Invalid argument name: ".$name."\n";
					exit;
				}

			} else {
				return [$name => $value];
			}

		} else {
			return NULL;
		}

	}


	/**
	 * Need help
	 */
	private function getHelp() {

		echo "Usage: php lime.php [options] [page] [arguments]\n\n".
		"[options]\n".
		"	-a appName\n".
		"		Use selected app\n".
		"	-r 'phpCode'\n".
		"		Run PHP code\n".
		"	-c NAME=VALUE\n".
		"		Define a PHP constant\n".
		"[page]\n".
		"	Page to run\n".
		"[arguments]\n".
		"	name=value\n".
		"		Add GET arguments to the page\n\n".
		"";

		exit;

	}

}
?>