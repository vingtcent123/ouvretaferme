<?php
/**
 * Test
 */
abstract class Test {

	private $ok = 0;
	private $ko = 0;
	private $trace = [];
	private $errorException = NULL;

	/**
	 * Test starts...
	 */
	public function init() {

	}

	/**
	 * Everytime a method starts...
	 */
	public function setUp() {

	}

	/**
	 * Everytime a method ends...
	 */
	public function tearDown() {

	}

	/**
	 * Test ends...
	 */
	public function finalize() {

	}

	/**
	 * Test if a value equals TRUE
	 *
	 * @param mixed $value
	 * @param string $message Message describing this test case
	 * @return bool
	 */
	protected function assertTrue($value, string $message = NULL): bool {
		return $this->assert($value === TRUE, TRUE, TRUE, $value, $message);
	}

	/**
	 * Test if a value equals FALSE
	 *
	 * @param mixed $value
	 * @param string $message Message describing this test case
	 * @return bool
	 */
	protected function assertFalse($value, string $message = NULL): bool {
		return $this->assert($value === FALSE, FALSE, TRUE, $value, $message);
	}

	/**
	 * This test always passes.
	 * It can be useful if you want to test that no exception has been thrown
	 *
	 * @return bool Always TRUE
	 */
	protected function assertPass(): bool {
		return $this->assert(TRUE, TRUE, TRUE, TRUE, NULL);
	}

	/**
	 * This test always fails
	 *
	 * @param string $message Message describing this test case
	 * @return bool Always FALSE
	 */
	protected function assertFail(string $message = NULL): bool {
		return $this->assert(NULL, FALSE, TRUE, TRUE, $message);
	}

	/**
	 * Test if a value equals a specific other value
	 *
	 * @param mixed $expected
	 * @param mixed $value
	 * @param string $message Message describing this test case
	 * @return bool
	 */
	protected function assertEquals($expected, $value, string $message = NULL): bool {
		return $this->assert($value === $expected, $expected, TRUE, $value, $message);
	}

	/**
	 * Test if a value differs a specific other value
	 *
	 * @param mixed $unexpected
	 * @param mixed $value
	 * @param string $message Message describing this test case
	 * @return bool
	 */
	protected function assertDiffer($unexpected, $value, string $message = NULL): bool {
		return $this->assert($value !== $unexpected, $unexpected, FALSE, $value, $message);
	}

	/**
	 * Test an exception
	 *
	 * @param Exception $e An exception
	 * @param string $message The expected exception message (FALSE to ignore this argument)
	 * @param int $code The expected exception message (FALSE to ignore this argument)
	 * @return TRUE if the test passed, FALSE otherwise
	 */
	protected function assertException($e, $message = '', $code = FALSE): bool {

		if($e instanceof Exception) {

			if($message !== '') {
				$flag = ($e->getMessage() === $message);
				$this->assert($flag, TRUE, TRUE, FALSE, "Exception message did not match");
				return $flag;
			}

			if($code !== FALSE) {
				$flag = ($e->getCode() === $code);
				$this->assert($flag, TRUE, TRUE, FALSE, "Exception message did not match");
				return $flag;
			}

		} else {
			$this->assertFail("Expected an exception, '".gettype($e)."' found");
		}

		return FALSE;

	}

	/**
	 * Test a method for an expected PHP error message.
	 *
	 * @param callable $callback A function callback
	 * @param array $args An array of arguments to give (FALSE if there is no arguments)
	 * @param string $message The expected error message (FALSE to ignore this argument)
	 * @param int $code The expected PHP error code (FALSE to ignore this argument)
	 */
	protected function assertError(callable $callback, $args = FALSE, $message = FALSE, $code = FALSE): bool {

		set_error_handler([$this, 'handleError']);

		try {

			if($args === FALSE) {
				call_user_func($callback);
			} else {
				call_user_func_array($callback, $args);
			}

			$errorException = $this->errorException;

		} catch(\Exception $e) {
			$errorException = $e;
		}

		restore_error_handler();
		$this->errorException = NULL;

		if($errorException instanceof Exception) {

			if($message !== FALSE) {
				return $this->assertEquals($errorException->getMessage(), $message, "Error message did not match");
			}

			if($code !== FALSE) {
				return $this->assertEquals($e->getCode(), $code, "Error type did not match");
			}

		} else {
			return $this->assertFail("Expected an error, none occurred");
		}

	}

	/**
	 * Test if an array has the same keys and values than an other one
	 *
	 * @param mixed $expected
	 * @param mixed $value
	 * @return bool
	 */
	protected function assertArraySimilar($expected, $compareTo): bool {

		if(!is_array($compareTo)) {
			return $this->assertFail("Invalid compare type : ".gettype($compareTo));
		}
		return $this->recursiveAssertArraySimilar($expected, $compareTo);
	}

	private function recursiveAssertArraySimilar($expected, $compareTo, $parentsKeys = []) {

		foreach($expected as $key => $value) {
			$includePath = array_merge($parentsKeys, [$key]);
			if(!array_key_exists($compareTo[$key])) {
				$this->assertFail("Could not find key ".implode(' => ', $includePath));
			} else if(is_array($compareTo[$key]) and is_array($value)) {
				$this->recursiveAssertArraySimilar($value, $compareTo[$key], $includePath);
			} else {
				$this->assertEquals($value, $compareTo[$key], "Values for key ".implode(' => ', $includePath)." does not match");
			}
		}

		foreach($compareTo as $key => $value) {
			if(!array_key_exists($key, $expected)) {
				$this->assertFail("Unexpected key ".implode(' => ', $includePath));
			}
		}
	}

	/**
	 * Test a value
	 *
	 * @param bool $valid
	 * @param mixed $compareTo Expected or Unexpected value
	 * @param bool $equality If the test was "===" or "!=="
	 * @param mixed $value Obtained value
	 * @param string $message Test message
	 */
	protected function assert(bool $valid, $compareTo, bool $equality, $value, string $message): bool {

		if($valid) {
			$this->ok++;
		} else {
			$this->ko++;

			$this->trace[$this->ko] = [
				$valid,
				$compareTo,
				$equality,
				$value,
				$message,
				array_slice(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), 1, -8),
			];

		}

		return $valid;

	}

	/**
	 * Show tests results
	 *
	 */
	public function show(): string {

		$className = get_class($this);

		$text = "";
		$text .= "+-------- ".bold($className)." -------+\n";
		$text .= "| Passed: ".bold($this->ok, "32")."".str_repeat(' ', strlen($className) - strlen($this->ok) + 8)."|\n";
		$text .= "| Failed: ".bold($this->ko, "31")."".str_repeat(' ', strlen($className) - strlen($this->ko) + 8)."|\n";
		$text .= "+--------".str_repeat('-', strlen($className) + 2)."-------+\n";

		foreach($this->trace as $test => $trace) {

			list($valid, $compareTo, $equality, $value, $message, $backtrace) = $trace;

			$in = $backtrace[0];

			$text .= "\n";
			$text .= "Test #".$test.": ".$in['function']."()\n";

			if($valid !== NULL) {
				if($equality === TRUE) {
					$text .= " * Expected: ".bold(var_export($compareTo, TRUE))."\n";
					$text .= " * Received: ".bold(var_export($value, TRUE))."\n";
				} else {
					$text .= " * Unexpected: ".bold(var_export($compareTo, TRUE))."\n";
				}
			}

			if($message !== NULL) {
				$text .= " * Message: ".bold($message)."\n";
			}

			if($backtrace !== []) {

				$text .= " * Stack trace:\n";
				$text .= \dev\TraceLib::getCli($backtrace, TRUE);

			}

		}

		return $text;
	}

	/**
	 * Convert PHP errors into an Exception.
	 *
	 * @param int $code
	 * @param string $message
	 * @param string $file
	 * @param string $line
	 * @throws Exception If the error is of type E_USER_ERROR or E_ERROR, the exception is thrown.
	 */
	public function handleError(int $code, string $message, string $file, string $line) {

		switch($code) {
			case E_USER_ERROR :
			case E_ERROR :
				throw new \Exception($message, $code, 0, $file, $line);
			default :
				$this->errorException = new \Exception($message, $code, 0, $file, $line);
		}

	}

}
?>
