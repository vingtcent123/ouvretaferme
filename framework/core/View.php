<?php

/**
 * Abstract class for all views
 */
abstract class View {

	/**
	 * List of registered views
	 *
	 * @var array
	 */
	private static $views = [];

	/**
	 * Callback function
	 */
	protected \Closure $callback;

	public function __construct(array|string|null $names, \Closure $callback) {

		if($names !== NULL) {

			if(is_string($names)) {
				$names = [$names];
			}

			foreach($names as $name) {

				if(isset(self::$views[$name])) {
					throw new Exception('Duplicate view \''.$name.'\'');
				}

				self::$views[$name] = $this;

			}

		}

		$this->callback = $callback;

	}

	/**
	 * Returns content type
	 *
	 */
	abstract public function getContentType(): string;

	abstract public function render(stdClass $data): void;

	public static function get(string $name, ViewAction $action): View {

		if(isset(self::$views[$name]) === FALSE) {
			trigger_error('View '.$name.' does not exist in '.$action->getViewFile().'', E_USER_ERROR);
			exit;
		}

		return self::$views[$name];

	}

	/**
	 * Get output with the given template
	 */
	protected function getOutput(stdClass $data): mixed {

		$template = $this->getTemplate($data);
		$callback = $this->callback;

		return $template->build(fn() => $callback->call($this, $data, $template));

	}

	protected function getTemplate(stdClass $data): Template {

		$parameters = (new ReflectionFunction($this->callback))->getParameters();

		if(count($parameters) !== 2) {
			throw new Exception('Callback function takes exactly 2 parameters (stdClass $data, Template $t)');
		}

		$name = $parameters[1]->getType()->getName();

		try {

			$reflection = new ReflectionClass($name);

			if($reflection->isSubclassOf('Template') === FALSE) {
				throw new Exception();
			}

			$t = $reflection->newInstance();
			$t->setData($data);

			return $t;

		} catch(Exception) {
			throw new Exception('Parameter 2 of callback function must be a Template object');
		}

	}

}

/**
 * Json views
 */
class JsonView extends View {

	/**
	 * Returns content type
	 *
	 */
	public function getContentType(): string {
		return 'application/json; charset=utf-8';
	}

	/**
	 * Create Json page
	 *
	 */
	public function render(stdClass $data): void {

		$output = $this->getOutput($data);

		echo self::formatArray($output);

	}

	/**
	 * Format an $output array to JSON
	 */
	public static function formatArray($output): string {

		if(LIME_ENV === 'dev') {
			$options = JSON_PRETTY_PRINT;
		} else {
			$options = 0;
		}

		return json_encode($output, $options);

	}

}

/**
 * HTML views
 */
class HtmlView extends View {


	/**
	 * Returns content type
	 *
	 * @return string
	 */
	public function getContentType(): string {
		return 'text/html; charset=utf-8';
	}

	public function render(stdClass $data): void {
		echo $this->getOutput($data);
	}

}

/**
 * Controls AJAX navigation
 */
class AdaptativeView extends View {

	public function getContentType(): string {

		return match(Route::getRequestedWith()) {

			'ajax' => 'application/json; charset=utf-8',
			default => 'text/html; charset=utf-8'

		};

	}

	public function render(stdClass $data): void {

		$output = $this->getOutput($data);

		echo match(Route::getRequestedWith()) {

			'ajax' => JsonView::formatArray($output),
			default => is_scalar($output) ? $output : JsonView::formatArray($output)

		};


	}

}
?>
