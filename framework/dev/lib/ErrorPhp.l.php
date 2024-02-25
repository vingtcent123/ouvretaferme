<?php
namespace dev;

/**
 * Error library
 */
class ErrorPhpLib {

	private static $count = 0;
	private static $list = [];

	private static $mode = LIME_ENV;

	/**
	 * Create an exception from a PHP error
	 * If set to TRUE, error is not saved in the database
	 *
	 * @var bool
	 */
	public static $createExceptionFromError = FALSE;

	/**
	 * Do nothing from a PHP error
	 *
	 * @var bool
	 */
	public static $doNothingFromError = FALSE;

	/**
	 * Save errors in the database ?
	 *
	 * @var bool
	 */
	public static $saveError = TRUE;

	/**
	 * Override user that triggered the error
	 *
	 * @var bool
	 */
	public static $overrideUser = NULL;

	/**
	 * Change error handler mode
	 * Default is LIME_ENV
	 *
	 */
	public static function setMode(string $mode) {
		self::$mode = $mode;
	}

	/**
	 * Specify if we have to create an exception from a PHP error
	 */
	public static function createExceptionFromError($status) {
		self::$createExceptionFromError = $status;
	}

	/**
	 * Specify if we have to do nothing from a PHP error
	 */
	public static function doNothingFromError($status) {
		self::$doNothingFromError = $status;
	}

	public static function saveError($status) {
		self::$saveError = $status;
	}

	/**
	 * Handle an error or an exception
	 * See handleException() and handleError() for more details
	 *
	 */
	public static function handle(...$arguments) {

		switch(func_num_args()) {

			// Exception
			case 1 :
				return ErrorPhpLib::handleException(...$arguments);

			// Error
			case 5 :
				return ErrorPhpLib::handleError(...$arguments);

		}

	}

	/**
	 * Display a backtrace as an error
	 */
	public static function handleFromBacktrace(array $backtrace, string $message = '') {

		return ErrorPhpLib::handleError(Error::UNEXPECTED, NULL, (string)$message, $backtrace[0]['file'], $backtrace[0]['line'], array_slice($backtrace, 1));

	}

	/**
	 * Handle an exception
	 */
	public static function handleException(\Throwable $e) {

		$eError = new Error([
			'message' => $e->getMessage(),
			'line' => $e->getLine(),
			'file' => $e->getFile(),
			'class' => get_class($e),
			'code' => $e->getCode(),
			'method' => \Route::getRequestMethod(),
			'type' => Error::EXCEPTION,
			'device' => \util\DeviceLib::get(),
			'referer' => mb_substr(SERVER('HTTP_REFERER'), 0, 255)
		]);
		self::buildMore($eError);

		$cErrorTrace = self::buildTrace($eError, $e->getTrace());
		$cErrorParameter = self::buildParameter($eError);

		if(self::$saveError) {
			self::handleProduction($eError, $cErrorTrace, $cErrorParameter);
		}

		if(self::$mode !== 'prod' or \Route::getRequestedWith() === 'cli') {
			self::handleDebug($eError, $cErrorTrace, $cErrorParameter);
		}

	}

	/**
	 * Handle a PHP error
	 */
	public static function handleError(string $type, int $code = NULL, string $message, string $file = NULL, string $line = NULL, array $trace = NULL, bool $deprecated = FALSE) {

		// Exclude errors with iconv(), getimagesize()
		if(
			strpos($message, 'iconv()') !== FALSE or
			strpos($message, 'getimagesize()') !== FALSE or
			strpos($message, 'Can not authenticate to IMAP server') !== FALSE
		) {
			return;
		}

		if(self::$doNothingFromError) {
			return;
		}

		if(self::$createExceptionFromError) {
			throw new \Exception($message.' in '.$file.' on line '.$line, $code);
		}

		$eError = new Error([
			'message' => $message,
			'line' => $line,
			'class' => NULL,
			'file' => $file === NULL ? NULL : substr($file, strlen(LIME_DIRECTORY)),
			'code' => self::getCode($code),
			'method' => \Route::getRequestMethod(),
			'type' => $type,
			'device' => \util\DeviceLib::get(),
			'referer' => SERVER('HTTP_REFERER'),
			'deprecated' => $deprecated
		]);

		if($deprecated) {
			$eError['status'] = Error::CLOSE;
		} else {
			$eError['status'] = Error::OPEN;
		}

		self::buildMore($eError);

		if($trace === NULL) {
			$trace = array_slice(debug_backtrace(FALSE, 50), 3);
		}

		$cErrorTrace = self::buildTrace($eError, $trace);
		$cErrorParameter = self::buildParameter($eError);

		if(self::$saveError) {
			self::handleProduction($eError, $cErrorTrace, $cErrorParameter);
		}

		if(
			(self::$mode !== 'prod' or \Route::getRequestedWith() === 'cli') and
			$code !== E_ERROR
		) {
			self::handleDebug($eError, $cErrorTrace, $cErrorParameter);
		}

	}

	/**
	 * Get an error code from a PHP error code
	 */
	public static function getCode(int $phpCode = NULL): string {

		switch($phpCode) {

			case E_USER_ERROR :
			case E_CORE_ERROR :
			case E_ERROR :
				return 'Fatal';

			case E_USER_WARNING :
			case E_CORE_WARNING :
			case E_WARNING :
				return 'Warning';

			case E_USER_NOTICE :
			case E_NOTICE :
			case E_STRICT :
				return 'Notice';

			default :
				return 'Other error';

		}

	}

	/**
	 * Delete old errors
	 *
	 */
	public static function clean() {

		$eError = Error::model()
			->select(['id', 'createdAt'])
			->where('createdAt < NOW() - INTERVAL '.\Setting::get('dev\errorKeep').' DAY')
			->sort(['createdAt' => SORT_DESC])
			->get();

		if($eError->notEmpty()) {

			ErrorParameter::model()
				->where('error <= '.$eError['id'])
				->delete();

			ErrorTrace::model()
				->where('error <= '.$eError['id'])
				->delete();

			Error::model()
				->where('id <= '.$eError['id'])
				->delete();

		}

	}

	/**
	 * Returns number of errors found
	 *
	 * @return int;
	 */
	public static function countErrors(): int {
		return self::$count;
	}

	/**
	 * Returns a list of errors found
	 *
	 * @return int;
	 */
	public static function listErrors(): array {
		return self::$list;
	}

	private static function handleProduction(Error $eError, \Collection $cErrorTrace, \Collection $cErrorParameter) {

		if(self::$doNothingFromError) {
			return;
		}

		self::$count++;

		if(\Setting::get('errorSaveMax') !== NULL) {

			if(self::$count > \Setting::get('errorSaveMax')) {
				return;
			}

		}

		try {

			Error::model()->insert($eError);

			if($cErrorTrace->notEmpty()) {
				ErrorTrace::model()->insert($cErrorTrace);
			}

			if($cErrorParameter->notEmpty()) {
				ErrorParameter::model()->insert($cErrorParameter);
			}

		}
		catch(\Exception $e) {

			self::saveError(FALSE);
			self::handle($e);
			self::saveError(TRUE);

		}

	}

	private static function handleDebug(Error $eError, \Collection $cErrorTrace, \Collection $cErrorParameter) {
		self::$count++;

		self::debug($eError, $cErrorTrace);

	}

	/**
	 * Write error text
	 */
	protected static function debug(Error $eError, \Collection $cErrorTrace) {

		self::$list[] = [$eError, $cErrorTrace];

		if(\Route::getRequestedWith() === 'cli') {
			echo self::debugCli($eError, $cErrorTrace);
		} else {
			echo self::debugHttp($eError, $cErrorTrace);
		}

	}

	public static function debugCli(Error $eError, \Collection $cErrorTrace): string {

		switch($eError['type']) {

			case Error::PHP :
				$title = $eError['code'];
				break;
			case Error::EXCEPTION :
				$title = 'PHP EXCEPTION';
				break;
			case Error::UNEXPECTED :
				$title = 'UNEXPECTED ACTION';
				break;
			case Error::NGINX :
				$title = 'NGINX ERROR';
				break;
		}

		$message = $eError['message'] ?: 'No message';

		$size = 100 - strlen($title) - 2;

		$h = "+".str_repeat('-', floor($size / 2))." ".$title." ".str_repeat('-', ceil($size / 2))."+\n";

		$h .= "  ".bold($message)."\n";
		$h .= " ".str_repeat('-', 100)."\n";
		if($eError['file'] and $eError['line']) {
			$h .= "  File: ".bold($eError['file'])." on line ".bold($eError['line'])."\n";
		}
		$h .= " ".str_repeat('-', 100)."\n";

		$h .= "  Stack trace:\n";
		$h .= str_replace("\n", "\n", rtrim(TraceLib::getCli($cErrorTrace)))."\n";

		$h .= "+".str_repeat('-', 100)."+\n";

		return $h;

	}

	public static function debugHttp(Error $eError, \Collection $cErrorTrace): string {

		if(\Route::getRequestedWith() === 'http') {

			\Asset::js('util', 'lime.js');
			\Asset::css('util', 'lime.css');
			\Asset::js('util', 'ajax.js');

			\Asset::css('dev', 'dev.css');
			\Asset::js('dev', 'dev.js');

			echo \Asset::importHtml();

		}

		$h = '<div class="dev-error-php">'."\n";
		$h .= '<h2>';
		switch($eError['type']) {
			case Error::PHP :
				$h .= $eError['code'];
				break;
			case Error::EXCEPTION :
				$h .= $eError['class'];
				break;
		}
		$h .= '</h2>'."\n";

		$message = $eError['message'] ?: '<i>No message</i>';

		$h .= '<p>'.encode($message).'</p>'."\n";

		if($eError['file'] or $eError['line']) {
			$h .= '<div class="dev-error-php-place">File: <b>'.$eError['file'].'</b> on line <b>'.$eError['line'].'</b></div>'."\n";
		}

		if($cErrorTrace->notEmpty()) {
			$h .= '<h4>Stack trace:</h4>'."\n";
			$h .= TraceLib::getHttp($cErrorTrace);
		}

		$h .= '</ul>'."\n";

		$h .= '</div>'."\n";

		return $h;

	}

	protected static function buildTrace(Error $eError, array $trace): \Collection {

		switch($eError['class']) {

			case 'ElementException' :
				$trace = array_slice($trace, 1);
				break;

		}

		$cErrorTrace = new \Collection();

		foreach($trace as $value) {

			$args = (array)($value['args'] ?? []);
			$args = self::buildArgs($args);

			$cErrorTrace[] = [
				'error' => $eError,
				'file' => $value['file'] ?? NULL,
				'line' => $value['line'] ?? NULL,
				'function' => $value['function'] ?? NULL,
				'class' => $value['class'] ?? NULL,
				'args' => $args ? serialize($args) : NULL,
			];

		}

		return $cErrorTrace;

	}

	protected static function buildArgs(array $args, int $loop = 0): array {

		$argsBuilded = [];
		foreach($args as $key => $arg) {
			if(is_array($arg) and $loop++ < 3) {
				$argsBuilded[$key] = self::buildArgs($arg, $loop);
			} else if(is_object($arg)) {
				$argsBuilded[$key] = 'Object '.get_class($arg);
			}
		}

		return $argsBuilded;

	}

	protected static function buildParameter(Error $eError): \Collection {

		$cErrorParameter = new \Collection();

		foreach(['get', 'post', 'cookie'] as $type) {

			switch($type) {
				case 'get' :
					$array = $_GET;
					break;
				case 'post' :
					$array = $_POST;
					break;
				case 'cookie' :
					$array = $_COOKIE;
					break;
			}

			foreach($array as $key => $value) {

				if(stristr($key, 'password') and is_string($value)) {
					$value = preg_replace(['/\d/', '/\w/'], '*', $value, 10);
				}

				$cErrorParameter[] = [
					'error' => $eError,
					'type' => $type,
					'name' => $key,
					'value' => serialize($value),
				];

			}

		}

		return $cErrorParameter;

	}

	/**
	 * Override user
	 */
	public static function overrideUser(\user\User $eUser) {
		self::$overrideUser = $eUser;
	}

	/**
	 * Builds additional information for the devError
	 */
	protected static function buildMore(Error $eError) {

		// Get server name
		$eError['server'] = php_uname('n');

		// Get browser
		$eError['browser'] = \util\HttpLib::getBrowserString();

		// Get request
		$eError['request'] = SERVER('HTTP_HOST').LIME_REQUEST;

		// Get app
		$eError['app'] = last(\Lime::getApps());

		// Get version
		$eError['tag'] = exec('git rev-parse --abbrev-ref HEAD');
		$eError['modeVersion'] = SERVER('HTTP_X_APP_VERSION', '?string', NULL);

		// Get module information
		$position = strpos($eError['message'], "|{");

		if($position !== FALSE) {
			$eError->add((array)json_decode(substr($eError['message'], $position+1, strlen($eError['message'])), TRUE));
			$eError['message'] = substr($eError['message'], 0, $position);
		}

		if(self::$overrideUser !== NULL) {
			$eError['user'] = self::$overrideUser;
		} else {

			$eUser = \user\ConnectionLib::getOnline();

			if($eUser->notEmpty()) {
				$eError['user'] = $eUser;
			}

		}

	}

	/**
	 * Display a 404 error
	 */
	public static function notFound(): string {

		$file = \Page::getOriginalPath();
		$target = first(\Page::getLastPages())[1];

		switch(\Route::getRequestedWith()) {

			case 'cli' :
				$methods = ['cli', 'cron', 'get', 'http'];
				break;

			default :
				$methods = [strtolower(\Route::getRequestMethod()), 'http'];
				break;

		}

		if(\Route::getRequestedWith() === 'cli') {
			return self::notFoundCli($file, $target, $methods);
		} else {
			return self::notFoundHttp($file, $target, $methods);
		}

	}

	protected static function notFoundHttp(string $file, string $target, array $methods): string {

		if(LIME_ENV !== 'dev') {
			return '';
		}

		$exists = is_file($file);

		$h = '';

		$h .= '<div class="dev-error-php">';
		$h .= '<h2>404 Not Found</h2>';
		$h .= '<ul>
			<li>Access: <b>'.\Route::getRequestedWith().'</b></li>
			<li>Request: <b>'.LIME_REQUEST.'</b></li>
			<li>Request method: <b>'.\Route::getRequestMethod().'</b></li>
		</ul>';

		$h .= '<h4>File tested:</h4>';

		$label = $exists ? '<span class="label label-success"><b>found</b></span>' : '<span class="label label-danger"><b>not found</b></span>';

		$h .= '<ul>
			<li>'.$label.' <b>'.$file.'</b></li>
		</ul>';

		if($exists) {

			$h .= '<h4>Compatible declarations for HTTP:</h4>';
			$h .= '<ul>';

			foreach($methods as $method) {

				if($target === 'index') {
					$h .= '<li><span class="label label-danger"><b>not found</b></span> Page::<b>'.$method.'</b>(<i>callback</i>)</li>';
				} else {
					$h .= '<li><span class="label label-danger"><b>not found</b></span> Page::<b>'.$method.'</b>(\'<b>'.$target.'</b>\', <i>callback</i>)</li>';
				}

			}

			$h .= '</ul>';

		}

		$h .= '</div>';

		return $h;

	}

	protected static function notFoundCli(string $file, string $target, array $methods): string {

		$title = '404 Not Found';
		$exists = is_file($file);

		$size = 100 - strlen($title) - 2;

		$h = "+".str_repeat('-', floor($size / 2))." ".$title." ".str_repeat('-', ceil($size / 2))."+\n";

		$h .= "  Access: ".bold(\Route::getRequestedWith())."\n";
		$h .= "  Request: ".bold(LIME_REQUEST)."\n";
		$h .= "  Request method: ".bold(\Route::getRequestMethod())."\n";

		$h .= " ".str_repeat('-', 100)."\n";

		$h .= "  ";
		$h .= bold($exists ? '[found]' : '[not found]');
		$h .= " ".$file."\n";


		if($exists) {

			$h .= " ".str_repeat('-', 100)."\n";

			$h .= "  Compatible declarations for CLI:\n";

			foreach($methods as $method) {

				if($target === 'index') {
					$h .= '   * '.bold('[not found]').' Page::'.$method.'([callback])'."\n";
				} else {
					$h .= '   * '.bold('[not found]').' Page::'.$method.'(\''.$target.'\', [callback])'."\n";
				}

			}

		}

		$h .= "+".str_repeat('-', 100)."+\n";

		return $h;

	}

}
?>
