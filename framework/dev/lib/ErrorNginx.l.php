<?php
namespace dev;

/**
 * Save Nginx errors
 */
class ErrorNginxLib {

	/**
	 * Extract Nginx log and add it to Error module
	 *
	 */
	public static function process() {

		// Get files to process since the last insertion
		$log = self::getLog();

		if($log['type'] === 'log') {

			// set file extension
			$ext = '.log';

			// set file path
			$log['file'] = \Setting::get('errorNginxPath').'/'.$log['folder'].'/'.$log['file'];

		} else if($log['type'] === 'bz2') {

			// set file extension
			$ext = '.log';

			// bunzip and export the compressed file into the /tmp folder
			$status = NULL;
			$output = NULL;
			exec('bzcat '.\Setting::get('errorNginxPath').'/'.$log['folder'].'/'.$log['file'].'.log.bz2 > /tmp/'.$log['file'].'.log', $output, $status);

			// Check bzcat returned value
			if($status !== 0) {
				trigger_error('bzcat returned errno '.$status.' : '.$output);
				return;
			}

			// set file path
			$log['file'] = '/tmp/'.$log['file'];

		} else {
			return;
		}

		$fileName = $log['file'].$ext;

		// Check file size
		if(filesize($fileName) > \Setting::get('errorNginxMaxLogSize') * 1024 * 1024) {
			trigger_error("Log file '".$fileName."' is too big (> ".\Setting::get('errorNginxMaxLogSize')."Mb)");
			return;
		}

		// open latest log
		$handle = fopen($fileName, 'rb');

		// init gError
		$cError = new \Collection();

		$minutes = date('i');

		if($minutes > \Setting::get('errorNginxInterval')) {
			$minutesMin = (int)((floor($minutes / \Setting::get('errorNginxInterval')) - 1) * \Setting::get('errorNginxInterval'));
		} else {
			$minutesMin = (int)(60 - \Setting::get('errorNginxInterval'));
		}

		$minutesMax = $minutesMin + \Setting::get('errorNginxInterval') - 1;

		$nextLine = fgets($handle, 1000000);
		$nextDate = NULL;

		self::hasDate($nextLine, $nextDate);

		$line = '';
		$date = $nextDate;
		$minute = (int)substr($date, 14, 2);

		while($nextLine !== FALSE) {

			$line .= $nextLine;
			$nextLine = fgets($handle, 1000000);

			if($nextLine === FALSE) {
				$runLine = TRUE;
			} else {
				$runLine = self::hasDate($nextLine, $nextDate);
			}

			if($runLine) {

				if($minute >= $minutesMin and $minute <= $minutesMax) {

					$eError = self::handleLine($line);

					if($eError->notEmpty()) {

						$eError['createdAt'] = $date;

						if($eError['code'] === Error::FATAL) {

							self::insertError($eError);

						} else {
							// Group errors by message to avoid multiple entries for the same error
							$key = $eError['message'];
							$cError[$key] = $eError;

						}

					}

				}

				$line = '';
				$date = $nextDate;
				$minute = (int)substr($date, 14, 2);

			}

		}

		fclose($handle);

		if($cError) {
			// insert dev errors
			foreach($cError as $eError) {
				self::insertError($eError);
			}
		}

		// clean /tmp folder if necessary
		if($log['type'] === 'bz2' and file_exists($log['file'].$ext)) {
			exec('rm '.$log['file'].$ext);
		}

	}

	protected static function hasDate(string $line, string &$date): bool {

		if(preg_match('/^([0-9]{4}\/[0-9]{2}\/[0-9]{2} [0-9]{2}\:[0-9]{2}\:[0-9]{2})/', $line, $match) > 0) {
			$date = str_replace('/', '-', $match[1]);
			return TRUE;
		} else {
			return FALSE;
		}

	}

	protected static function getLog(): array {

		$timestamp = time();

		$minutes = date('i');

		// from minutes 0 to \Setting::get('errorNginxInterval') - 1 we parse the archived file
		// from \Setting::get('errorNginxInterval') to 59 we parse the current error file

		if($minutes < \Setting::get('errorNginxInterval')) {
			// generate file path
			$file = strftime(\Setting::get('errorNginxFilePattern'), $timestamp);
			$folder = sprintf("%02d", date('m', $timestamp));
		} else {
			$file = \Setting::get('errorNginxActiveFile');
			$folder = ".";
		}

		return [
			'file' => $file,
			'folder' => $folder,
			'type' => self::getFileType($folder, $file),
		];

	}

	protected static function handleLine(string $line): Error {

		// skip empty lines
		if(trim($line) === '') {
			return new Error();
		}

		// get filters
		$filters = self::conf('errorNginxFilters');

		// determine error level
		if(stripos($line, 'fatal') !== FALSE or strpos($line, 'Segmentation fault') !== FALSE) {
			$code = E_ERROR;
		} else if(stripos($line, 'warn') !== FALSE) {
			$code = E_WARNING;
		} else {
			$code = E_NOTICE;
		}

		// skip lines containing forbidden words (only for non fatal errors)
		if($code !== Error::FATAL) {

			foreach($filters['word'] as $filterWords) {
				if(stripos($line, $filterWords) !== FALSE) {
					return new Error();
				}
			}

			// skip lines containing forbidden regular expressions
			foreach($filters['regex'] as $filterRegex) {
				//preg match return 1 if pattern matches else 0 or false if errors
				if(preg_match($filterRegex, $line) === 1) {
					return new Error();
				}
			}

		}

		// extract file
		$file = self::getDataFromLine(self::getPattern('file'), $line);

		if($file === NULL) {
			if(\Setting::get('errorNginxReportAll') === TRUE) {
				$file = '';
			} else {
				return new Error();
			}
		} else if(strpos($file, LIME_DIRECTORY) !== FALSE) {
			$file = substr($file, strlen(LIME_DIRECTORY));
		} else {
			return new Error();
		}

		// extract message
		$message = NULL;
		foreach(['stackMessage', 'fastCGIMessage', 'defaultMessage'] as $pattern) {
			$message = self::getDataFromLine(self::getPattern($pattern), $line);
			if($message !== NULL) {
				break;
			}
		}

		// extract line number
		$lineNumber = self::getDataFromLine(self::getPattern('line'), $line);

		if($lineNumber === NULL) {
			$lineNumber = 0;
		}

		// extract request
		$request = self::getDataFromLine(self::getPattern('request'), $line);

		// build devError element
		$eError = new Error([
			'message' => $message,
			'line' => $lineNumber,
			'file' => $file,
			'code' => $code,
			'mode' => Error::HTTP,
			'type' => Error::NGINX,
			'server' => php_uname('n'),
			'device' => \util\Device::model()->get(),
			'referer' => SERVER('HTTP_REFERER')
		]);

		if($request !== NULL) {
			$eError['request'] = $request;
		}

		// extract stack trace
		$eError['trace'] = self::extractStackTrace($eError, $line);

		return $eError;
	}


	/**
	 * Extract a stack trace from an error line.
	 *
	 * @param type $line
	 * @return type
	 */
	protected static function extractStackTrace(Error $eError, string $line): \Collection {

		$cErrorTrace = new \Collection();

		$line = str_replace("\n", "", $line);
		$stringStack = self::getDataFromLine(self::getPattern('stackTrace'), $line);

		if($stringStack !== NULL) {

			$stackTrace = explode("#", $stringStack);
			array_shift($stackTrace);

			foreach($stackTrace as $element) {

				// Clean trace
				$cleanTrace = self::getDataFromLine(self::getPattern('cleanTrace'), $element);

				// Extract Elements
				$traceLine = self::getDataFromLine(self::getPattern('line'), $cleanTrace);
				if($traceLine === NULL) {
					$traceLine = self::getDataFromLine(self::getPattern('traceLine'), $cleanTrace);
				}
				$traceFile = self::getDataFromLine(self::getPattern('traceFile'), $cleanTrace);
				$traceFile = str_replace(LIME_DIRECTORY, '', $traceFile);
				$traceClass = self::getDataFromLine(self::getPattern('traceClass'), $cleanTrace);
				$traceFunction = self::getDataFromLine(self::getPattern('traceFunction'), $cleanTrace);
				$traceArgs = self::getDataFromLine(self::getPattern('traceArgs'), $cleanTrace);

				// Build trace
				$cErrorTrace[] = [
					'error' => $eError,
					'file' => $traceFile,
					'line' => $traceLine,
					'class' => $traceClass,
					'function' => $traceFunction,
					'arguments' => $traceArgs
				];

			}
		}

		return $cErrorTrace;

	}

	/**
	 * Insert an error and its trace in database
	 *
	 * @param type $eError
	 */
	protected static function insertError(Error $eError) {

		Error::model()->insert($eError);

		if($eError['trace']) {
			ErrorTrace::model()->insert($eError['trace']);
		}

	}

	protected static function getPattern(string $type): string {
		switch($type) {

			case 'date':
				return "/[0-9]{4}\/[0-9]{2}\/[0-9]{2} [0-9]{2}\:[0-9]{2}\:[0-9]{2}/";

			case 'file':
				return "/\/[\D]*\/.*?[\S]*/";

			case 'line':
				return "/on line ([0-9]{1,})/";

			case 'request':
				return "/request: \"(POST|GET) (.*) HTTP\/1\.1\"/";

			case 'stackTrace':
				return "/Stack trace:(.*)\" while reading response header from upstream/";

			case 'cleanTrace':
				return "/[0-9]* (.*)/";

			case 'fastCGIMessage':
				return "/FastCGI sent in stderr: \"PHP message: (.*)\" while reading response header from upstream/s";

			case 'defaultMessage':
				return "/(.*)/";

			case 'stackMessage':
				return "/FastCGI sent in stderr: \"PHP message: (.*)Stack trace:.*/s";

			case 'traceFile':
				return "/\/[\D]*\/.*\.php/";

			case 'traceLine':
				return "/\(([0-9]*)\)/";

			case 'traceClass':
				return "/[\s]+([\D]*)\-\>/";

			case 'traceFunction':
				return "/\-\>(.*)\(/";

			case 'traceArgs':
				return "/\-\>.*\((.*)\)/";
		}
	}

	protected static function getFileType(string $folder, string $file) {

		if(file_exists(\Setting::get('errorNginxPath').'/'.$folder.'/'.$file.'.log')) {
			return 'log';
		} else if(file_exists(\Setting::get('errorNginxPath').'/'.$folder.'/'.$file.'.log.bz2')) {
			return 'bz2';
		} else {
			return NULL;
		}
	}

	protected static function getDataFromLine(string $pattern, string $line) {

		$result = NULL;
		preg_match($pattern, $line, $result);
		return $result[count($result) - 1] ?? NULL;

	}

}
?>
