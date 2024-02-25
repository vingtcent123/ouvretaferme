<?php

/**
 * Action once the page has been created
 *
 * @author Olivier Issaly
 */
abstract class Action extends \Exception {

	const JSON = 'application/json; charset=utf-8';
	const HTML = 'text/html; charset=utf-8';

	/**
	 * Enable/disable Gzip compression
	 */
	protected bool $gzip = FALSE;

	/**
	 * Content type
	 */
	protected ?string $contentType = NULL;

	/**
	 * Content disposition
	 */
	protected ?string $contentDisposition = NULL;

	/**
	 * Count executed actions
	 */
	private static int $count = 0;

	/**
	 * Execute the action
	 */
	abstract public function run();

	/**
	 * New action ran
	 */
	public static function newRun(): void {
		self::$count++;
	}

	/**
	 * Count ran actions
	 */
	public static function ran(): int {
		return self::$count;
	}

	/**
	 * Enable/disable gzip compression
	 *
	 * @param bool $gzip
	 */
	public function setGzip($gzip): void {
		$this->gzip = (bool)$gzip;
	}

	/**
	 * Change HTTP status code
	 *
	 * @param int $code New code
	 */
	public function setStatusCode(int $code): string {

		$header = $this->getHeaderCode($code);
		header('HTTP/1.0 '.$header);

		return $header;

	}

	public function getHeaderCode(int $code): string {

		return match($code) {
			200 => '200 OK',
			201 => '201 Created',
			301 => '301 Moved Permanently',
			302 => '302 Moved Temporarily',
			304 => '304 Not Modified',
			400 => '400 Bad Request',
			401 => '401 Unauthorized',
			403 => '403 Forbidden',
			404 => '404 Not Found',
			405 => '405 Method Not Allowed',
			408 => '408 Request Timeout',
			409 => '409 Conflict',
			410 => '410 Gone',
			500 => '500 Internal Server Error',
			501 => '501 Not Implemented',
			502 => '502 Bad Gateway',
			503 => '503 Service Unavailable',
			default => throw new Exception('Unknown HTTP code')
		};

	}

	/**
	 * Change the current content type
	 *
	 * @param string $contentType
	 */
	public function setContentType(string $contentType) {
		$this->contentType = $contentType;
	}

	/**
	 * Checks if a content type has been set
	 *
	 * @return bool
	 */
	public function hasContentType() {
		return $this->contentType !== NULL;
	}

	/**
	 * Get the current content type
	 *
	 * @param string
	 */
	public function getContentType(): string {

		if($this->contentType === NULL) {

			switch(Route::getRequestedWith()) {

				case 'http' :
				case 'cli' :
					return self::HTML;

				default  :
					return self::JSON;

			}

		} else {
			return $this->contentType;
		}

	}

	/**
	 * Send the content type
	 */
	public function sendContentType() {

		if(
			Route::getRequestedWith() !== 'cli' and
			headers_sent() === FALSE
		) {
			header('Content-Type: '.$this->getContentType());
		}

	}

	public function sendContentDisposition() {
		if($this->contentDisposition !== NULL) {
			header('Content-Disposition: '.$this->contentDisposition);
		}
	}

}

/**
 * Null action: nothing is done
 *
 * @author Olivier Issaly
 */
class VoidAction extends Action {

	/**
	 * Nothing is done
	 */
	public function run(): void {

		switch(Route::getRequestedWith()) {

			case 'ajax' :
				(new JsonAction([]))->run();
				break;

			default :
				break;

		}

	}

}

/**
 * Status action: nothing is done and a code is sent to the browser
 */
class StatusAction extends Action {

	public function __construct(int $code) {

		$header = $this->setStatusCode($code);

		if(Route::getRequestedWith() === 'cli') {
			echo $header."\n";
		}

		parent::__construct();

	}

	public function run(): void {

	}

}

/**
 * Flow action: print arbitrary data
 */
class DataAction extends Action {

	/**
	 * Some data
	 *
	 * @var string
	 */
	protected $data = '';

	/**
	 * Create the action with some data
	 *
	 * @param string $data
	 * @param string $contentType
	 */
	public function __construct(string $data = '', string $contentType = Action::HTML) {

		if($data !== NULL) {
			$this->set($data);
		}

		if($contentType !== NULL) {
			$this->setContentType($contentType);
		}

		parent::__construct();

	}

	/**
	 * Set some data do print
	 *
	 * @param string $data
	 */
	public function set(string $data) {
		$this->data = (string)$data;
	}

	/**
	 * Get current data
	 *
	 * @return string
	 */
	public function get() {
		return $this->data;
	}

	/**
	 * Print data
	 */
	public function run(): void {

		$this->sendContentType();

		echo $this->data;

	}

}

/**
 * User input is wrong and you don't want to explain why to him (ie: user tried to hack something)
 */
abstract class NotAction extends Action {

	protected ?string $wrong = NULL;
	protected string $request;

	protected ?Action $alternateAction;

	/**
	 * Create the action
	 *
	 * @param string $message Internal message (for debug only)
	 * @param Action $alternateAction Action thrown in 'preprod' and 'prod' mode (default is 404 error)
	 */
	public function __construct(string $message, ?Action $alternateAction) {

		if(LIME_ENV === 'dev' or LIME_ENV === 'preprod' or LIME_ENV === 'prod') {

			$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

			if(
				($backtrace[1]['class'] ?? NULL) === 'Action' and
				($backtrace[1]['function'] ?? NULL) === '__callStatic'
			) {
				$start = 2;
			} else {
				$start = 1;
			}

			$backtrace = array_slice($backtrace, $start);

			ob_start();
			\dev\ErrorPhpLib::handleFromBacktrace($backtrace, $message);
			$this->wrong = ob_get_clean();

		}

		$this->alternateAction = $alternateAction;

	}

	protected function convert($value): string {

		$text = '';

		if($value !== NULL) {

			if($value instanceof Element) {
				$text .= ': '.$value->getModule();
				if(isset($value['id'])) {
					$text .= ' #'.$value['id'];
				}
				$text .= '';
			} else if(is_scalar($value)) {
				$text .= ': '.$value;
			}

		}

		return $text;

	}

	public function run(): void {

		if($this->alternateAction) {
			$this->alternateAction->run();
		} else {

			$data = new stdClass();
			$data->error = $this->wrong;

			Page::run($this->request, data: $data);

		}

	}

}

class NotExistsAction extends NotAction {

	protected string $request = 'error:404';

	public function __construct($value = '', ?Action $alternateAction = NULL, ?string $error = NULL) {

		$message = 'Data does not exist';
		$message .= $this->convert($value);

		if($error) {
			$message .= ' ('.$error.')';
		}

		parent::__construct($message, $alternateAction);

	}

}

class NotExpectedAction extends NotAction {

	protected string $request = 'error:404';

	public function __construct($value = '', ?Action $alternateAction = NULL, ?string $error = NULL) {

		$message = 'Data is unexpected';
		$message .= $this->convert($value);

		if($error) {
			$message .= ' ('.$error.')';
		}

		parent::__construct($message, $alternateAction);

	}

}

class NotAllowedAction extends NotAction {

	protected string $request = 'error:403';

	public function __construct($value = '', ?Action $alternateAction = NULL, ?string $error = NULL) {

		$message = 'Data access is not allowed';
		$message .= $this->convert($value);

		if($error) {
			$message .= ' ('.$error.')';
		}

		parent::__construct($message, $alternateAction);

	}

}

/**
 * FailAction: handle fails
 */
class FailAction extends Action {

	/**
	 * Create the action with some data
	 *
	 * @param mixed $fail A FailWatch object or a fail string
	 */
	public function __construct(
		protected FailWatch|string $fail,
		protected array $arguments = []
	) {

	}

	public function run(): void {

		if(is_string($this->fail)) {
			$fw = Fail::watch($this->fail, $this->arguments);
		} else {
			$fw = $this->fail;
		}

		switch(Route::getRequestedWith()) {

			case 'cli' :
			case 'http' :
				(new DataAction((string)$fw."\n"))->run();
				break;

			default :

				$t = new AjaxTemplate();
				$t->js()->errors($fw);

				(new JsonAction($t
					->pushInstructions()
					->getOutput()))->run();

				break;

		}

	}

}

/**
 * LineAction
 */
class LineAction extends DataAction {

	/**
	 * Create the action with some data
	 *
	 */
	public function __construct(string $message) {
		parent::__construct($message."\n");
	}

}

class PageAction extends Action {

	public function __construct(
		protected string $request
	) {

	}

	public function run(): void {
		Page::run($this->request);
	}

}

/**
 * Reload action: reload the current page
 */
class ReloadAction extends Action {

	protected bool $layer = FALSE;

	public function __construct(
		protected string $package = '',
		protected string $fqn = ''
	) {

	}

	public function run(): void {

		switch(Route::getRequestedWith()) {

			case 'cli' :
			case 'http' :

				$url = $_SERVER['REQUEST_URI'];

				if($this->package and $this->fqn) {
					$url .= strpos($url, '?') === FALSE ? '?' : '&';
					$url .= 'success='.$this->package.':'.$this->fqn;
				}

				throw new RedirectAction($url);

			case 'ajax' :

				$t = new AjaxTemplate();

				if($this->layer) {
					$t->ajaxReloadLayer();
				} else {
					$t->ajaxReload();
				}

				if($this->package and $this->fqn) {
					$t->js()->success($this->package, $this->fqn);
				}

				(new JsonAction($t
					->pushInstructions()
					->getOutput()))->run();

		}

	}

}

class ReloadLayerAction extends ReloadAction {

	protected bool $layer = TRUE;

}

/**
 * History action: navigates in the history
 */
class HistoryAction extends Action {

	public function __construct(
		protected int $number,
		protected string $package = '',
		protected string $fqn = ''
	) {

	}

	public function run(): void {

		switch(Route::getRequestedWith()) {

			case 'cli' :
			case 'http' :
				throw new DataAction('<script>history.go('.$this->number.')</script>');

			case 'ajax' :

				$t = new AjaxTemplate();
				$t->js()->moveHistory($this->number);

				if($this->package and $this->fqn) {
					$t->js()->success($this->package, $this->fqn);
				}

				(new JsonAction($t
					->pushInstructions()
					->getOutput()))->run();

		}

	}

}

/**
 * History action: go back to history
 */
class BackAction extends HistoryAction {

	public function __construct(string $package = '',string $fqn = '') {
		parent::__construct(-1, $package, $fqn);
	}

}

/**
 * History action: go forward to history
 */
class ForwardAction extends HistoryAction {

	public function __construct(string $package = '',string $fqn = '') {
		parent::__construct(1, $package, $fqn);
	}

}

/**
 * Action wich consists to redirect to a specific URL
 *
 * @author Olivier Issaly
 */
class RedirectAction extends Action {

	/**
	 * Http status
	 *
	 * @var string
	 */
	protected string $httpStatus = "HTTP/1.1 301 Moved Permanently";


	/**
	 * RedirectAction constructor
	 *
	 * @param string $url URL to redirect to
	 * @param stdClass $data Add data for JSON output only
	 */
	public function __construct(
		protected string $url,
		protected string $mode = 'assign'
	) {

	}

	public function run(): void {

		switch(Route::getRequestedWith()) {

			case 'http' :

				header($this->httpStatus);
				header('Location: '.$this->url);

				break;

			case 'cli' :
				echo "Redirect: ".$this->url."\n";
				break;

			default :
				$this->setContentType('application/json; charset=utf-8');
				$this->sendContentType();

				$json = (new AjaxTemplate())
				   ->redirect($this->url, $this->mode)
					->pushInstructions()
					->getOutput();

				(new JsonAction($json))->run();
				break;

		}
	}

}

/**
 * Action wich consists to redirect permanently to a specific URL
 *
 * @author Olivier Issaly
 */
class PermanentRedirectAction extends RedirectAction {


}

/**
 * Action wich consists to redirect temporarily to a specific URL
 *
 * @author Olivier Issaly
 */
class TemporaryRedirectAction extends RedirectAction {

	protected string $httpStatus = "HTTP/1.1 302 Moved Temporarily";

}

/**
 * Takes an array and produces JSON
 */
class JsonAction extends Action {

	/**
	 * Content type
	 *
	 * @var string
	 */
	protected ?string $contentType = 'application/json; charset=utf-8';

	/**
	 * Some data
	 *
	 * @var array
	 */
	protected $data = [];

	/**
	 * Create a JsonAction with some data
	 *
	 * @param array $data
	 */
	public function __construct(array $data = []) {

		$this->data = $data;

		parent::__construct();


	}

	/**
	 * Print data
	 */
	public function run(): void {

		$this->sendContentType();

		echo JsonView::formatArray($this->data);

	}

}

/**
 * Takes an array and outputs CSV
 *
 * @author Émilie Guth
 */
class CsvAction extends Action {

	/**
	 * Content type
	 */
	protected ?string $contentType = 'application/csv; charset=utf-8';

	/**
	 * Some lines
	 */
	protected array $lines;

	/**
	 * Create a CsvAction with some lines
	 */
	public function __construct(array $lines, string $filename) {

		$this->lines = $lines;
		$this->contentDisposition = 'attachment; filename="'.$filename.'"';

		parent::__construct();


	}

	/**
	 * Print data
	 */
	public function run(): void {

		$this->sendContentType();
		$this->sendContentDisposition();

		$fp = fopen('php://memory', 'r+');

		foreach($this->lines as $line) {
			fputcsv($fp, $line);
		}

		rewind($fp);

		echo stream_get_contents($fp);

		fclose($fp);

	}

}

/**
 * Takes a string and outputs PDF
 *
 */
class PdfAction extends Action {

	/**
	 * Content type
	 *
	 * @var string
	 */
	protected ?string $contentType = 'application/pdf; charset=utf-8';

	/**
	 * Some data
	 *
	 * @var array
	 */
	protected $data;

	/**
	 * Create a CsvAction with some data
	 *
	 * @param array $data
	 */
	public function __construct(string $data, string $filename) {

		$this->data = $data;
		$this->contentDisposition = 'attachment; filename="'.$filename.'"';

		parent::__construct();


	}

	/**
	 * Print data
	 */
	public function run(): void {

		$this->sendContentType();
		$this->sendContentDisposition();

		echo $this->data;

	}

}

/**
 * Action which lets a PHP script to format
 *
 * @author Olivier Issaly
 */
class ViewAction extends Action {

	/**
	 * View filename
	 *
	 */
	protected ?string $viewFile;

	/**
	 * View name
	 */
	protected ?string $viewName;

	public function __construct(
		private ?stdClass $data = NULL,
		protected ?string $path = NULL
	) {

		if($this->data === NULL) {
			$this->data = new stdClass;
		}

	}

	public function getViewFile(): ?string {
		return $this->viewFile;
	}

	public function getViewName(): ?string {
		return $this->viewName;
	}

	public function run(): void {

		if($this->path === NULL) {

			$request = Page::getRequest();

			$this->viewFile = Package::getFileFromUri($request, 'view');
			$this->viewName = Page::getName();

		} else {

			if(strpos($this->path, ':') === 0) {

				$request = Page::getRequest();

				$this->viewFile = Package::getFileFromUri($request, 'view');
				$this->viewName = substr($this->path, 1);

			} else {

				if(strpos($this->path, ':') !== FALSE) {

					$request = strstr($this->path, ':', TRUE);

					$this->viewFile = Package::getFileFromUri($request, 'view');
					$this->viewName = substr($this->path, strpos($this->path, ':') + 1);

				} else {

					$request = $this->path;

					$this->viewFile = Package::getFileFromUri($request, 'view');
					$this->viewName = 'index';

				}

			}

		}

		if($this->viewFile === NULL) {

			trigger_error("View '".$this->path."' does not exist", E_USER_ERROR);
			exit;

		} else {

			require_once $this->viewFile;

		}

		if($this->gzip) {
			ob_start('ob_gzhandler');
		} else {
			ob_start();
		}

		$view = View::get($this->viewName, $this);

		if($this->hasContentType() === FALSE) {
			$this->setContentType($view->getContentType());
		}

		$this->sendContentType();

		$view->render($this->data);

	}

}

?>
