<?php
/*
 * Load class if needed
 */
spl_autoload_register(function (string $class) {

	switch($class) {
		case 'Filter' :
			return require_once LIME_DIRECTORY.'/framework/core/Filter.php';
		case 'Fail' :
		case 'FailWatch' :
			return require_once LIME_DIRECTORY.'/framework/core/Fail.php';
		case 'Parallel' :
			return require_once LIME_DIRECTORY.'/framework/core/Parallel.php';
		case 'Database' :
			return require_once LIME_DIRECTORY.'/framework/core/Database/Database.php';
		case 'DatabaseManager' :
			return require_once LIME_DIRECTORY.'/framework/core/Database/DatabaseManager.php';
		case 'ReflectionApp' :
			return require_once LIME_DIRECTORY.'/framework/core/Reflection/ReflectionApp.php';
		case 'ReflectionPackage' :
			return require_once LIME_DIRECTORY.'/framework/core/Reflection/ReflectionPackage.php';
		case 'Migration' :
			return require_once LIME_DIRECTORY.'/framework/core/Migration.php';
		case 'MigrationAdministration' :
			return require_once LIME_DIRECTORY.'/framework/core/MigrationAdministration.php';
		case 'ModuleAdministration' :
			return require_once LIME_DIRECTORY.'/framework/core/ModuleAdministration.php';
		case 'Test' :
			return require_once LIME_DIRECTORY.'/framework/core/Test.php';
		case 'L' :
			return require_once LIME_DIRECTORY.'/framework/core/Language.php';
		case 'Cache' :
		case 'MemCacheCache' :
		case 'RedisCache' :
		case 'FileCache' :
		case 'DbCache' :
		case 'EmptyCache' :
			return require_once LIME_DIRECTORY.'/framework/core/Cache.php';

		case 'Asset' :
			return require_once LIME_DIRECTORY.'/framework/core/Asset.php';

	}

	if(strpos($class, '\\') !== FALSE) {

		list($package, $base) = explode('\\', $class, 2);

		if(str_ends_with($base, 'ObserverLib')) {
			$path = Package::getFile(substr($base, 0, -11), 'observer-lib', $package);
		} else if(str_ends_with($base, 'Lib')) {
			$path = Package::getFile(substr($base, 0, -3), 'lib', $package);
		} else if(str_ends_with($base, 'ObserverUi')) {
			$path = Package::getFile(substr($base, 0, -10), 'observer-ui', $package);
		} else if(str_ends_with($base, 'Ui')) {
			$path = Package::getFile(substr($base, 0, -2), 'ui', $package);
		} else if(str_ends_with($base, 'Test')) {
			$path = Package::getFile(substr($base, 0, -4), 'test', $package);
		} else if(str_ends_with($base, 'Page')) {
			$path = Package::getFile(substr($base, 0, -4), 'module', $package);
		} else if(str_ends_with($base, 'Crud')) {
			$path = Package::getFile(substr($base, 0, -4), 'module', $package);
		} else if(str_ends_with($base, 'Element')) {

			$path = Package::getFile(substr($base, 0, -7), 'module', $package);

			if($path === NULL) {
				eval('namespace '.$package.'; trait '.$base.' {}');
			}

		} else if(str_ends_with($base, 'Model')) {
			$path = Package::getFile(substr($base, 0, -5), 'module', $package);
		} else {
			$path = Package::getFile($base, 'element', $package);
		}

	} else {

		if(str_ends_with($class, 'Template')) {
			$path = Package::getFile(substr($class, 0, -8), 'template', 'main');
		} else {
			$path = NULL;
		}

	}

	if($path !== NULL) {
		require_once $path;
	}

});


/*
 * Get several attributes for a HTML markup
 */
function attrs(array $attributes, string $prefix = ''): string {

	$values = [];

	foreach($attributes as $name => $value) {

		if($value !== NULL) {

			if(ctype_digit((string)$name)) {
				$values[] = $value;
			} else {

				$key = $prefix.preg_replace_callback('/([A-Z])/', fn($value) => '-'.strtolower($value[1]), $name);
				$values[] = attr($key, $value);

			}

		}

	}

	return implode(' ', $values);

}

/*
 * Get an encoded attribute for a HTML markup
 */
function attr(string $name, ?string $value): string {
	if($value !== NULL) {
		return $name.'="'.encode($value).'"';
	} else {
		return '';
	}
}

function attrAjaxBody(array $values): string {
	return attr('data-ajax-body', json_encode($values));
}

/**
 * Checks if some properties have been set for an array and throws an exception if not
 */
function array_expects(array $input, $keys, ?callable $callback = NULL): mixed {

	$check = function(array $input, array $keys) use($callback): array {

		$lacks = [];

		foreach($keys as $key => $value) {

			if(is_string($key)) {
				$property = $key;
				$value = (array)$value;
			} else if(is_string($value)) {
				$property = $value;
			} else {
				throw new Exception('Invalid keys');
			}

			if(array_key_exists($property, $input) === FALSE) {
				$lacks[] = $property;
			} else if(is_array($value)) {

				if(is_array($input[$key])) {
					$result = array_expects($input[$key], $value, $callback);
					if($result) {
						$lacks[] = $property.'['.implode(', ', $result).']';
					}
				} else {
					$lacks[] = $property.'[Invalid type]';
				}


			}

		}

		return $lacks;

	};

	$lacks = $check($input, (array)$keys);

	if($callback === NULL) {

		$callback = function(array $lacks) {

			if($lacks) {
				throw new Exception(p(
					'Property '.implode(', ', $lacks).' is not set',
					'Properties '.implode(', ', $lacks).' are not set',
					count($lacks)
				));
			}

		};

	}

	return $callback($lacks);

}

function array_delete(&$array, $key): bool {

	$position = array_search($key, $array, TRUE);

	if($position !== FALSE) {
		unset($array[$position]);
		return TRUE;
	} else {
		return FALSE;
	}

}

function get_collator(): Collator {

	$collator = new Collator('UTF-8');
	$collator->setAttribute(Collator::FRENCH_COLLATION, Collator::ON);

	return $collator;

}

/**
 * Checks if a string is one of the keys
 */
function str_is(string $input, $keys, ?callable $callback = NULL) {

	if(in_array($input, (array)$keys, TRUE) === FALSE) {

		if($callback === NULL) {

			$callback = function(array $input) {
				throw new Exception('Input \''.$input.'\' is not as expected');
			};

		}

		return $callback($input);

	}

}

function analyze_url(string $url): array {

	if(preg_match('/(http[s]?):\/\/(([a-z0-9\.\-]+\.)?([a-z0-9\-]+\.[a-z]+))(:([0-9]+))?\/?/i', $url, $match) > 0) {

		$host = $match[2];
		$domain = $match[4];
		$protocol = strtolower($match[1]);

		if(isset($match[6])) {
			$port = (int)$match[6];
		} else {
			$port = NULL;
		}

		return [
			'host' => $host,
			'domain' => $domain,
			'protocol' => $protocol,
			'port' => $port
		];

	} else {
		throw new Exception('Url \''.$url.'\' is invalid');
	}

}

/**
 * Display a variable for debugging
 */
function d(...$args) {

	if(count($args) > 1) {
		if(Route::getRequestedWith() !== 'cli') {
			echo '<div style="z-index: 100000;
				background-color: #555;
				color: white;
				padding: 10px;
				margin-bottom: 10px;">';
		}
	}

	$display = function($value): void {

		if(Route::getRequestedWith() !== 'cli') {
			echo '<pre style="z-index: 100000;
				background-color: black;
				color: white;
				padding: 5px;">';
		}

		echo $value;

		if(Route::getRequestedWith() !== 'cli') {
			echo '</pre>';
		}

	};

	foreach($args as $arg) {
		if($arg instanceof Collection) {
			$display($arg);
		} else if($arg instanceof Exception) {
			echo \dev\ErrorPhpLib::handle($arg);
		} else if($arg instanceof DOMElement) {

			$doc = new DOMDocument;
			$node = $doc->importNode($arg, true);
			$doc->appendChild($node);

			$display('DomElement: '.encode($doc->saveHTML()));

		} else {

			ob_start();

			if($arg instanceof Element) {
				echo $arg;
			} else if($arg instanceof ArrayObject) {
				echo get_class($arg).': ';
				var_dump($arg->getArrayCopy());
			} else {
				var_dump($arg);
			}

			$content = ob_get_clean();

			$display($content);

		}
	}

	if(count($args) > 1) {
		if(Route::getRequestedWith() !== 'cli') {
			echo '</div>';
		}
	}

}

/**
 * Display a variable for debugging and exit
 */
function dd(...$args) {
	d(...$args);
	exit;

}

/**
 * Create bold text for CLI
 *
 * @param string $text
 */
function bold(string $text) {
	return "\033[01m".$text."\033[00m";
}

/*
 * Retrieve defined constants
 */
function getConstants() {
	return SERVER('constants', 'array');
}

/*
 * Retrieve a variable from $_SERVER
 */
function SERVER(string $name, $cast = 'string', $default = NULL) {

	$value = $_SERVER[$name] ?? NULL;
	return var_filter($value, $cast, $default);

}

function server_exists(string $name): bool {
	return isset($_SERVER[$name]);
}

/*
 * Retrieve a variable by POST method
 */
function POST(string $name, $cast = 'string', $default = NULL) {

	$value = $_POST[$name] ?? NULL;
	return var_filter($value, $cast, $default);

}

function post_exists(string $name): bool {
	return isset($_POST[$name]);
}

/**
 * Retrieve a variable from a put HTTP request
 */

function allPut() {
	global $_PUT;
	if($_PUT === NULL) {
		parse_str(file_get_contents("php://input"), $_PUT);
	}
	return $_PUT;

}

function PUT(string $name, $cast = 'string', $default = NULL) {
	$_PUT = allPut();
	$value = $_PUT[$name] ?? NULL;
	return var_filter($value, $cast, $default);
}

function put_exists(string $name): bool {
	$_PUT = allPut();
	return isset($_PUT[$name]);
}

/*
 * Retrieve a variable by GET method
 */
function GET(string $name, $cast = 'string', $default = NULL) {

	$value = $_GET[$name] ?? NULL;
	return var_filter($value, $cast, $default);

}

function get_exists(string $name): bool {
	return isset($_GET[$name]);
}

/*
 * Retrieve a variable by COOKIE method
 */
function COOKIE(string $name, $cast = 'string', $default = NULL) {

	$value = $_COOKIE[$name] ?? NULL;
	return var_filter($value, $cast, $default);

}

function cookie_exists(string $name): bool {
	return isset($_COOKIE[$name]);
}

/*
 * Retrieve a variable by either GET, POST or COOKIE methods
 */
function REQUEST(string $name, $cast = 'string', $default = NULL) {

	$value = $_REQUEST[$name] ?? NULL;
	return var_filter($value, $cast, $default);

}

function request_exists(string $name): bool {
	return isset($_REQUEST[$name]);
}

/*
 * Retrieve a variable from GET or POST depending of the request method
 */
function INPUT(string $name, $cast = 'string', $default = NULL) {

	return match(Route::getRequestMethod()) {
		'GET' => GET($name, $cast, $default),
		'POST' => POST($name, $cast, $default),
		default => throw new Exception('Unhandled method '.Route::getRequestMethod())
	};

}

function input_exists(string $name): bool {

	return match(Route::getRequestMethod()) {
		'GET' => get_exists($name),
		'POST' => post_exists($name),
		default => throw new Exception('Unhandled method '.Route::getRequestMethod())
	};

}

function var_filter(mixed $value, mixed $cast, mixed $default = NULL) {

	$runDefault = function() use ($value, $default) {
		return is_closure($default) ? $default($value) : $default;
	};

	if(is_closure($cast)) {
		return $cast($value) ? $value : $runDefault();
	} else if(is_array($cast)) {
		return in_array($value, $cast) ? $value : $runDefault();
	} else {

		if($cast[0] === '?') {
			$cast = substr($cast, 1);
			$nullable = TRUE;
		} else {
			$nullable = FALSE;
		}

		if($nullable) {
			if($value === '' or $value === []) {
				return NULL;
			}
		}

		if($value === NULL) {

			if($default === NULL) {

				switch($cast) {

					case 'int' :
						return $nullable ? NULL : 0;
					case 'bool' :
						return $nullable ? NULL : FALSE;
					case 'float' :
						return $nullable ? NULL : 0.0;
					case 'array' :
						return $nullable ? NULL : [];
					case 'string' :
					case 'binary' :
						return $nullable ? NULL : '';
					case 'json' :
						return $nullable ? NULL : [];

					case 'element' :
						return new Element();

					default :
						if(strpos($cast, '\\') !== FALSE) {
							return new $cast;
						} else {
							return NULL;
						}

				}

			} else {
				return $runDefault();
			}

		} else {
			return cast($value, $cast);
		}

	}


}

/*
 * Lowercase the first character of a string with mb functions
 */
function mb_lcfirst(string $str): string {
	return mb_strtolower(mb_substr($str, 0, 1)).mb_substr($str, 1);
}

/*
 * Uppercase the first character of a string with mb functions
 */
function mb_ucfirst(string $str): string {
	return mb_strtoupper(mb_substr($str, 0, 1)).mb_substr($str, 1);
}

/*
 * Encode HTML string
 */
function encode(string $content = NULL): string {
	if(is_scalar($content) or is_null($content)) {
		return htmlspecialchars((string)$content);
	} else {
		return getType($content);
	}
}

/*
 * Decode HTML string
 */
function decode(string $content = NULL): string {
	if(is_scalar($content) or is_null($content)) {
		return htmlspecialchars_decode((string)$content);
	} else {
		return getType($content);
	}
}

/*
 * Ensure that a string contains only valid UTF-8
 */
function str_utf8(string &$string) {
	$string = @iconv('UTF-8', 'UTF-8//IGNORE', $string);
}

/**
 * Turns a string into a string that can be used as Fully qualified name
 *
 * @param string $string
 * @param string $separator
 * @return string
 */
function toFqn(string $string, string $separator = '-'): string {

	$string = mb_strtolower($string);
	$string = str_replace(
		[
			'ð', 'Þ',
			'ó', 'ö', // Doublon UTF8
			'–', 'œ'
		],
		[
			'd', 'th',
			'ó', 'ö',
			'-', 'oe'
		],
		$string
	);

	$transliterator = Transliterator::createFromRules(':: Any-Latin; :: Latin-ASCII; :: NFD; :: [:Nonspacing Mark:] Remove; :: NFC;', Transliterator::FORWARD);
	$string = $transliterator->transliterate($string);
	$string = preg_replace('/\s+/si', $separator, $string);
	$string = preg_replace('/[^a-z0-9'.preg_quote($separator, '/').']+/si', $separator, $string);

	if($separator) {

		// trim the double dashes
		while(strpos($string, $separator.$separator) !== FALSE) {
			$string = str_replace($separator.$separator, $separator, $string);
		}

		$string = trim($string, $separator);

	}

	return $string;
}

function isFqn(string $string) {
	return preg_match('/^[a-z0-9][a-z0-9\-]*$/', $string) === 1;
}

/**
 * Turns a string into a string formatted as a constant
 *
 * @param string $string
 * @return string
 */
function toConstant(string $string): string {
	$string = mb_strtoupper($string);
	$string = preg_replace('/[^a-z0-9]+/si', '_', $string);
	$string = trim($string, '_');

	return $string;
}

/*
 * Get real user IP address
 */
function getIp(): string {

	$list = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';

	$ips = preg_split("/\s*,\s*/", $list);

	foreach($ips as $ip) {
		if(isIp($ip) === TRUE and isLocalIp($ip) === FALSE) {
			return $ip;
		}
	}

	return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

}

/*
 * Get user referer
 */
function getReferer(string $suffix = ''): string {

	$referer = SERVER('HTTP_REFERER');

	if($suffix !== '') {
		if(strpos($referer, '?') !== FALSE) {
			$referer .= '&';
		} else {
			$referer .= '?';
		}
		$referer .= $suffix;
	}

	return $referer;

}

/*
 * Get unique id
 */
function getId(string $prefix = 'id'): string {
	return $prefix.time().((float)microtime(false) * 1000000);
}

/**
 * Returns TRUE if the probability is reached, FALSE otherwise
 *
 * @param int $chance
 *
 * @return bool
 */
function luck(float $chance): bool {
	return (mt_rand(1, 10000) <= (int)($chance * 100));
}

/*
 * Get textual date and time
 */
function currentDatetime(): string {
	return toDatetime(time());
}

function toDatetime(int|string  $timestamp): string {
	return toPeriod('Y-m-d H:i:s', $timestamp);
}

function currentYear(): int {
	return date('Y');
}

function currentDate(): string {
	return toDate(time());
}

function currentMonth(): string {
	return date('Y-m');
}

function toDate(int|string  $timestamp): string {
	return toPeriod('Y-m-d', $timestamp);
}

function toMonth(int|string  $timestamp): string {
	return toPeriod('Y-m', $timestamp);
}

function currentTime(): string {
	return toTime(time());
}

function toTime(int|string  $timestamp): string {
	return date('H:i:s', $timestamp);
}

function currentWeek(): string {
	return toWeek(time());
}

function getWeeksInYear($year) {
	$date = new DateTime;
	$date->setISODate($year, 53);
	return ($date->format("W") === "53" ? 53 : 52);
}


function toWeek(int|string $timestamp): string {
	return toPeriod('o-\WW', $timestamp);
}

function toPeriod(string $pattern, int|string $timestamp): string {

	if(is_string($timestamp)) {
		$timestamp = strtotime($timestamp);
	} else if($timestamp === NULL) {
		$timestamp = time();
	}

	return date($pattern, $timestamp);

}

function week_year(string $week): ?int {
	return (strlen($week) >= 4) ? (int)substr($week, 0, 4) : NULL;
}

function week_number(string $week): ?int {
	return (strlen($week) === 8 and $week[5] === 'W') ? (int)substr($week, 6, 2) : NULL;
}

function week_dates(string $week): array {

	$dates = [];

	for($day = 0; $day <= 6; $day++) {
		$dates[] = date('Y-m-d', strtotime($week.' + '.$day.' DAYS'));
	}

	return $dates;

}

function week_date_starts(string $week): string {
	return date('Y-m-d', strtotime($week));
}

function week_date_ends(string $week): string {
	return date('Y-m-d', strtotime($week.' + 6 DAYS'));
}

function week_date_day(string $week, int $day): string {
	return date('Y-m-d', strtotime($week.' + '.($day - 1).' DAYS'));
}

function date_year(string $date): ?int {
	return (strlen($date) >= 7 and $date[4] === '-') ? (int)substr($date, 0, 4) : NULL;
}

function date_month(string $date): ?int {
	return (strlen($date) >= 4) ? (int)substr($date, 5, 2) : NULL;
}

function time_float(string $time): ?float {
	return substr($time, 0, 2) + substr($time, 3, 2) / 60 + substr($time, 6, 2) / 3600;
}

function time_from_float(float $time): string {

	$minutes = ($time - (int)$time) * 60;
	$seconds = ($minutes - (int)$minutes) * 60;

	return sprintf('%02d', (int)$time).':'.sprintf('%02d', (int)$minutes).':'.sprintf('%02d', (int)$seconds);

}

/**
 * Check if a timezone exists
 *
 * @param string $timeZone
 */
function isTimeZone(string $timeZone): bool {
	return in_array((string)$timeZone, DateTimeZone::listIdentifiers(DateTimeZone::ALL_WITH_BC), TRUE);
}

function s(string $text, $args = []) {
	return L::s(NULL, $text, $args);
}

function p(string $singular, string $plural, int|float $number, $args = []) {
	return L::p(NULL, $singular, $plural, $number, $args);
}

/*
 * Check for XML text
 */
function isXml(string $xml): bool {

	dev\ErrorPhpLib::createExceptionFromError(TRUE);

	try {
		$doc = new DOMDocument();
		$valid = @$doc->loadXML($xml);
	} catch(Exception $e) {
		$valid = FALSE;
	}

	dev\ErrorPhpLib::createExceptionFromError(FALSE);

	return $valid;

}

/*
 * Check for HTML text
 */
function isHtml(string $html): bool {

	dev\ErrorPhpLib::createExceptionFromError(TRUE);

	try {
		$doc = new DOMDocument();
		$valid = @$doc->loadHTML($html);
	} catch(Exception $e) {
		$valid = FALSE;
	}

	dev\ErrorPhpLib::createExceptionFromError(FALSE);

	return $valid;

}

/*
 * Check for IP address
 */
function isIp(string $ip): bool {

	$ipUnit = "(?:25[0-5]|2[0-4][0-9]|[0-1]?[0-9]{1,2})";
	return preg_match('/^'.$ipUnit.'\.'.$ipUnit.'\.'.$ipUnit.'\.'.$ipUnit.'$/', $ip) > 0;

}

/*
 * Check for local IPs
 */
function isLocalIp(string $ip): bool {

	$int = ip2long($ip);

	return (
		(sprintf("%u", ip2long('10.0.0.0')) <= sprintf("%u", $int) and sprintf("%u", $int) <= sprintf("%u", ip2long('10.255.255.255'))) or
		(sprintf("%u", ip2long('172.16.0.0')) <= sprintf("%u", $int) and sprintf("%u", $int) <= sprintf("%u", ip2long('172.31.255.255'))) or
		(sprintf("%u", ip2long('192.168.0.0')) <= sprintf("%u", $int) and sprintf("%u", $int) <= sprintf("%u", ip2long('192.168.255.255'))) or
		(sprintf("%u", ip2long('169.254.0.0')) <= sprintf("%u", $int) and sprintf("%u", $int) <= sprintf("%u", ip2long('169.254.255.255')))
	);

}

/*
 * Get the first entry of an array
 */
function first($array) {
	return reset($array);
}

/*
 * Get the last entry of an array
 */
function last($array) {
	return end($array);
}

/*
 * Get a DateTime instance and modify the given $timestamp (date.timezone hour) to match the $newTimezone
 */
function getDateTime(string $timestamp, string $newTimezone): string {

	if(is_numeric($timestamp)) {
		$timestamp = '@'.$timestamp;
	}

	$date = new DateTime($timestamp);
	$date->setTimezone(new DateTimeZone($newTimezone));

	return $date;
}

/*
 * Get the value of the specified keys
 */
function array_extract(array $array, array $keys) {

	$output = [];

	foreach($array as $key => $value) {

		if(in_array($key, $keys)) {
			$output[$key] = $value;
		}

	}

	return $output;

}

/*
 * Ensure that an array contains only valid UTF-8
 */
function array_utf8(array &$array) {

	foreach($array as $key => $value) {
		if(is_array($value)) {
			array_utf8($array[$key]);
		} else {
			str_utf8($array[$key]);
		}
	}

}

/*
 * Test is a variable is a closure
 */
function is_closure($value) {
    return is_object($value) and ($value instanceof Closure);
}

/*
 * Cast a variable
 *
 * @param mixed $cast String (int, bool, float...) or array with authorized values
 */
function cast($value, string $cast) {

	switch($cast) {
		case 'int' :
			return (int)$value;
		case 'bool' :
			return (bool)$value;
		case 'float' :
			return (float)$value;
		case 'array' :
			return (array)$value;
		case 'string' :
			return is_scalar($value) ? trim((string)$value) : '';
		case 'json' :
			return is_string($value) ? (json_decode(trim($value), TRUE) ?? []) : [];
		case 'binary' :
			return $value;

		default :

			if(strpos($cast, '\\') !== FALSE) {

				if(empty($value)) {
					return new $cast;
				}

				if($value instanceof Element) {
					return $value;
				}

				if(is_array($value)) {
					return new $cast($value);
				}

				$value = (string)$value;

				$cast::model()->cast('id', $value);

				return new $cast([
					'id' => $value
				]);

			} else {
				throw new Exception('Invalid cast \''.$cast.'\'');
			}

	}

}

/*
 * Display a nice backtrace
 */
function backtrace($export = FALSE) {

	$backtrace = debug_backtrace();

	foreach($backtrace as $key => $value) {
		if(isset($value['object'])) {
			$backtrace[$key]['object'] = get_class($value['object']);
		}
		if(isset($value['args'])) {
			$backtrace[$key]['args'] = count($value['args']);
		}
	}

	if($export) {
		return $backtrace;
	} else {
		dev\ErrorPhpLib::handleFromBacktrace($backtrace);
	}

}

/*
 * Check execution time
 */
global $timer;

$timer = [];

function startTimer() {
	global $timer;
	array_push($timer, microtime(TRUE));
}

function stopTimer(int $precision = 5) {
	global $timer;
	return round((microtime(TRUE) - array_pop($timer)), $precision);
}

function get_request_headers() {
	$out = [];
	foreach($_SERVER as $key => $value) {
		if(strpos($key, 'HTTP_') === 0) {
			$key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
			$out[$key] = $value;
		}
	}
	return $out;
}

/*
 * Check that IP match the given mask
 */
function isIPIn(string $givenIp, $expectedNets, string $mask = '255.255.255.255'): bool {

	if(!isIp($givenIp)) {
		return FALSE;
	}

	$expectedNets = (array) $expectedNets;
	$valid = FALSE;
	$givenIpLong = ip2long($givenIp);
	$mask = ip2long($mask);

	foreach($expectedNets as $expectedNet) {
		if(!isIp($expectedNet)) {
			continue;
		}

		$expectedNet = ip2long($expectedNet);
		$valid |= (($givenIpLong & $mask) === ($expectedNet & $mask));
	}
	return $valid;
}
?>
