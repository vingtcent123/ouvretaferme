<?php
namespace language;


/**
 * Tools to export modules into CSV files
 */
class CsvLib {

	// Codes for exceptions
	const WRONG_COLUMN_NUMBER = 1;
	const UNKNOWN_CHARSET = 2;
	const NOT_CSV = 3;
	const KEY_CORRUPTED = 4;
	const CHESS = 5;
	const LINE_CORRUPTED = 6;

	/**
	 * Contextual package
	 *
	 * @var \ReflectionPackage
	 */
	protected $package;

	/**
	 * Create a new instance
	 *
	 * @param ReflectionPackage $package The package
	 */
	public function __construct(\ReflectionPackage $package) {

		$this->package = $package;

	}

	/**
	 * Get messages from a CSV file
	 *
	 * @param string $lang
	 * @return array
	 */
	public function load(string $lang): array {

		$pathCsv = $this->package->getPath().'/'.\Setting::get('directoryImport').'/'.$lang.'.csv';

		if(is_file($pathCsv) === FALSE) {
			return [];
		}

		$lines = $this->importCsv($lang, $pathCsv);
		$this->cleanCsv($lines, $pathCsv);

		$messages = $this->buildMessages($lines, $lang, $pathCsv);

		return $messages;

	}

	/**
	 * Import CSV content
	 *
	 * @param string $lang
	 * @param string $pathCsv Path to CSV file
	 * @return string
	 */
	public function importCsv(string $lang, string $pathCsv): array {

		$charsetCheck = exec('file -i '.$pathCsv);

		if(preg_match("/charset=([a-z0-9\-]+)/si", $charsetCheck, $match)) {

			$charset = strtoupper($match[1]);

			if($charset === 'UNKNOWN-8BIT') {

				if(substr($lang, 0, 2) === 'tr') {
					$charset = 'ISO-8859-9';
				} else if(substr($lang, 0, 2) === 'sv') {
					$charset = 'ISO-8859-4';
				} else {
					$charset = 'ISO-8859-15';
				}

			}
			if(strpos($charset, 'UNKNOWN') === 0) {
				throw new \Exception("Unknown charset '".$charset."' in file '".$pathCsv."'", self::UNKNOWN_CHARSET);
			}

		} else {
				throw new \Exception("No charset found in file '".$pathCsv."'", self::UNKNOWN_CHARSET);
		}

		$content = file_get_contents($pathCsv);

		// Check encoding
		if($charset !== 'UTF-8') {

			$contentUtf8Ignore = iconv($charset, "UTF-8//IGNORE", $content);
			$contentUtf8 = iconv($charset, "UTF-8", $content);

			if($contentUtf8 !== $contentUtf8Ignore) {
				throw new \Exception("No charset found in file '".$pathCsv."'", self::UNKNOWN_CHARSET);
			} else {
				$content = $contentUtf8;
			}

		}

		// Special formatting
		if(strpos($charset, 'ISO-8859-') === 0) {
			$content = str_replace(chr(133), '...', $content);
		}

		$lines = \util\CsvLib::parseCsv('data://text/plain;base64,'.base64_encode($content));

		if($lines === []) {
			throw new \Exception("Can not parse '".$pathCsv."'", self::NOT_CSV);
		}

		return $lines;

	}

	/**
	 * Clean CSV lines (remove comments and check chess characters)
	 *
	 * @param array $lines
	 * @param string $pathCsv
	 * @return array
	 */
	public function cleanCsv(array &$lines, string $pathCsv) {

		$hasChess = FALSE;

		foreach($lines as $position => $line) {

			// Empty line
			if(trim($line[0]) === '') {
				unset($lines[$position]);
			}
			// Comment
			else if(strpos($line[0], '#') === 0) {

				if(count($line) >= 3 and strpos($line[2], '♙ ♟ ♔ ♕ ♖ ♗ ♘ ♚ ♛ ♜ ♝ ♞') !== FALSE) {
					$hasChess = TRUE;
				}

				unset($lines[$position]);

			}

		}

		if($hasChess === FALSE) {
			throw new \Exception("Could not find chess characters in file '".$pathCsv."'", self::CHESS);
		}

	}

	/**
	 * Build texts from CSV content
	 *
	 * @param array $lines
	 * @param array $lang
	 * @param string $pathCsv
	 * @return array
	 */
	public function buildMessages(array $lines, string $lang, string $pathCsv): array {

		$messagesByPath = [];

		foreach($lines as $position => $line) {

			if(count($line) !== 3 and count($line) !== 6) {
				throw new Exception("Wrong number of columns on line ".$position." in file ".$pathCsv, self::WRONG_COLUMN_NUMBER);
			}

			$key = trim($line[0]);
			$text = MessageLib::formatText($line[2], $lang);

			switch(substr_count($key, '-')) {

				case 1 :

					list($id, $file) = explode('-', $key);
					$file = $this->package->getPath().'/lang/'.$lang.'/'.$file.'.m.php';

					$messagesByPath[$file][(int)$id] = $text;

					break;

				case 2 :

					list($type, $id, $file) = explode('-', $key);
					$file = $this->package->getPath().'/lang/'.$lang.'/'.$file.'.m.php';

					$messagesByPath[$file][(int)$id][(int)$type] = $text;

					break;

				default :
					throw new Exception("First column is corrupted on line ".$position." in file ".$pathCsv, self::LINE_CORRUPTED);

			}

		}

		return $messagesByPath;

	}

	/**
	 * Create CSV string for exported texts
	 *
	 * @param string $lang
	 * @param array $export
	 * @return string
	 */
	public function create(string $lang, array $export): string {

		$hasOld = FALSE;

		$lines = [];

		foreach($export as $element) {

			$line = [
				$this->getKey($element),
				$this->getDescription($lang, $element),
				$element['text']
			];

			// Add an old version of the text if we can found it
			if($element['old'] !== NULL and $element['oldSource'] !== NULL) {

				$hasOld = TRUE;

				$line[] = '';
				$line[] = $element['oldSource'];
				$line[] = $element['old'];

			}

			$lines[] = $line;

		};

		$lines = array_merge(
			$this->getHead($lang, $hasOld),
			$lines
		);

		$csv = \util\CsvLib::toCsv($lines);

		return $csv;

	}

	/**
	 * Save CSV file
	 *
	 * @param string $lang
	 * @param string $csv
	 */
	public function save(string $lang, string $csv) {

		$directoryCsv = $this->package->getPath().'/'.\Setting::get('directoryExport').'/';

		if(is_dir($directoryCsv) === FALSE) {
			mkdir($directoryCsv, 0755, TRUE);
		}

		$pathCsv = $directoryCsv.'/'.$lang.'.csv';

		file_put_contents($pathCsv, $csv);

	}

	/**
	 * Get unique key of the given message
	 * [type]-[id]-[file]
	 *
	 * @param array $element
	 */
	private function getKey(array $element): string {

		$key = '';

		if($element['type'] !== NULL) {
			$key = $element['type'].'-';
		}

		$key .= $element['id'].'-';
		$key .= substr($element['file'], strlen($this->package->getPath().'/lang/') + 6, - strlen('.m.php'));

		return $key;

	}

	/**
	 * Return description of the given message
	 *
	 * @param string $lang
	 * @param array $element
	 * @return string
	 */
	private function getDescription(string $lang, array $element): string {

		$description = [];

		$types = \L::countTypes($lang);

		// No number
		if($element['type'] === NULL) {

		}
		// Singular
		else if($element['type'] === 0) {

			if($types > 1) {
				$description[] = 'Singular';
			}

		}
		// Plural
		else {

			if($types === 2) {
				$description[] = 'Plural';
			} else {
				$description[] = 'Plural '.$element['type'];
			}

		}

		// Custom calls
		if($element['custom'] !== NULL) {
			$description = array_merge($description, $element['custom']);
		}

		// Comments
		if($element['comments']) {
			$description = array_merge($description, $element['comments']);
		}

		// New version of an existing text?
		if($element['old'] !== NULL and $element['oldSource'] !== NULL) {

			$description[] = "New version of an existing text";

		}


		return implode(' / ', $description);

	}

	/**
	 * Return head for CSV files
	 *
	 * @param string $lang
	 * @param bool $hasOld
	 * @return string
	 */
	protected function getHead(string $lang, bool $hasOld): array {

		$lines = [];

		/** Add file heading **/
		$lines[] = [
			'### Compatibility check',
			'',
			'- If you can not see the chess pieces then the file is corrupted: ♙ ♟ ♔ ♕ ♖ ♗ ♘ ♚ ♛ ♜ ♝ ♞',
		];

		$lines[] = [
			'###',
			'',
			'- In this case, you may try to open the file using UTF-8 encoding.',
		];

		$lines[] = [
			'',
			'',
			'',
		];

		/* Add Plural specificities (when several forms of plural) */
		$pluralForms = $this->getPluralForms($lang);

		if($pluralForms) {

			$lines[] = [
				'### Plural forms for context:',
				'',
				'',
			];

			foreach($pluralForms as $form => $pluralForm) {
				if($form == '0') {
					$form = "Singular";
				} else {
					$form = 'Plural '.$form;
				}
				$lines[] = [
					'### - '.$form,
					$pluralForm['rule'],
					$pluralForm['example'],
				];
			}

			$lines[] = [
				'',
				'',
				'',
			];

		}

		/** Add column names **/
		$columns = [
			'### ID ###',
			'### Context ###',
			'### Message to translate ###',
		];

		if($hasOld) {
			$columns[] = '';
			$columns[] = '### Former message ###';
			$columns[] = '### Former translation ###';
		}

		$lines[] = $columns;

		return $lines;

	}

	/**
	 * Return a list of plural forms for a lang
	 *
	 * @param string $lang
	 * @return array
	 */
	private function getPluralForms(string $lang): array {

		$plural = [];

		switch($lang) {

			case 'cs_CZ' :
			case 'sk_SK' :

				$plural = [
					'0' => [
						'rule' => 'for 1',
						'example' => '1'
					],
					'1' => [
						'rule' => 'for 2, 3, 4',
						'example' => '2, 3, 4'
					],
					'2' => [
						'rule' => 'everything else',
						'example' => '5, 6, 7, 8, 9, 10, 11, 12, 13, 14, ...'
					]
				];

				break;

			case 'ru_RU' :
			case 'ru_UA' :

				$plural = [
					'0' => [
						'rule' => 'ends with 1, except 11',
						'example' => '1, 21, 31, 41, 51, 61, 71, 81, 91, 101, 121, 131, 141, 151, 161, 171, 181, 191, 201, 221, 231, 241, 251, 261, 271, 281, 291, ... (example: 1 лошадь)'
					],
					'1' => [
						'rule' => 'ends with 2-4, except 12-14',
						'example' => '2, 3, 4, 22, 23, 24, 32, 33, 34, 42, 43, 44, 52, 53, 54, 62, 63, 64, 72, 73, 74, 82, 83, 84, 92, 93, 94, 102, 103, 104, 122, 123, 124, 132, 133, 134, 142, 143, 144, 152, 153, 154, 162, 163, 164, 172, 173, 174, 182, 183, ... (example: 3 лошади, 24 лошади)'
					],
					'2' => [
						'rule' => 'everything else',
						'example' => '0, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 25, 26, 27, 28, 29, 30, 35, 36, 37, 38, 39, 40, 45, 46, 47, 48, 49, 50, 55, 56, 57, 58, 59, 60, 65, 66, 67, 68, 69, 70, 75, 76, 77, ... (example: 7 лошадей, 48 лошадей)'
					]
				];

				break;

			case 'pl_PL' :

				$plural = [
					'0' => [
						'rule' => 'for 1',
						'example' => '1 (example: 1 godzina)'
					],
					'1' => [
						'rule' => 'ends with 2-4, except 12-14',
						'example' => '2, 3, 4, 22, 23, 24, 32, 33, 34, 42, 43, 44, 52, 53, 54, 62, 63, 64, 72, 73, 74, 82, 83, 84, 92, 93, 94, 102, 103, 104, 122, 123, 124, 132, 133, 134, 142, 143, 144, 152, 153, 154, 162, 163, 164, 172, 173, 174, 182, 183, ... (example: 3 godziny, 24 godziny)'
					],
					'2' => [
						'rule' => 'everything else',
						'example' => '0, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 25, 26, 27, 28, 29, 30, 31, 35, 36, 37, 38, 39, 40, 41, 45, 46, 47, 48, 49, 50, 51, 55, 56, 57, 58, 59, 60, 61, 65, 66, 67, 68, ... (example: 7 godzin, 48 godzin)'
					]
				];

				break;

			case 'sl_SI' :

				$plural = [
					'0' => [
						'rule' => 'ends with 01',
						'example' => '1, 101, 201, ...'
					],
					'1' => [
						'rule' => 'ends with 02',
						'example' => '2, 102, 202, ...'
					],
					'2' => [
						'rule' => 'ends with 03-04',
						'example' => '3, 4, 103, 104, 203, 204'
					],
					'3' => [
						'rule' => 'everything else',
						'example' => '0, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, ...'
					]
				];

				break;

			case 'lv_LV' :

				$plural = [
					'0' => [
						'rule' => 'for 0 and 1',
						'example' => '0, 1'
					],
					'1' => [
						'rule' => 'everything else',
						'example' => '2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 22, 23, 24, 25, 26, 27, 28, 29, 30, 32, 33, 34, 35, 36, 37, 38, 39, 40, 42, 43, 44, 45, 46, 47, 48, 49, 50, 52, 53, 54, 55, ...'
					]
				];

				break;

			case 'lt_LT' :

				$plural = [
					'0' => [
						'rule' => 'ends with 1 except 11',
						'example' => '1, 21, 31, 41, 51, 61, 71, 81, 91, 101, 121, 131, 141, 151, 161, 171, 181, 191, 201, 221, 231, 241, 251, 261, 271, 281, 291 ...'
					],
					'1' => [
						'rule' => 'ends with 0, or with 10-20',
						'example' => '0, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 30, 40, 50, 60, 70, 80, 90, 100, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 130, 140, 150, 160, 170, 180, 190, 200, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220 ...'
					],
					'2' => [
						'rule' => 'everything else',
						'example' => '2, 3, 4, 5, 6, 7, 8, 9, 22, 23, 24, 25, 26, 27, 28, 29, 32, 33, 34, 35, 36, 37, 38, 39, 42, 43, 44, 45, 46, 47, 48, 49, 52, 53, 54, 55, 56, 57, 58, 59, 62, 63, 64, 65, 66, 67, 68, 69, 72, 73, ...'
					]
				];

				break;
		}

		return $plural;

	}

}
?>
