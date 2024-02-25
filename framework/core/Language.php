<?php

/**
 * Use to easily translate texts
 */
class L {

	/**
	 * Registered translations
	 *
	 * @var array
	 */
	protected static array $languages = [];


	/**
	 * Collator singleton with the lang
	 *
	 * @var array
	 */
	protected static array $collator = [];

	/**
	 * Registered files
	 *
	 * @var array
	 */
	protected static array $files = [];

	protected static ?string $currentLang;
	protected static string $defaultLang = 'fr_FR';

	private static bool $debug = FALSE;
	private static array $debugList = [];

	private static $lastId;

	private static array $variables = [];

	/**
	 * Enable/Disabled debug mode
	 *
	 * @param bool $status New debug status (true/false)
	 */
	public static function debug($status) {
		self::$debug = (bool)$status;
	}

	/**
	 * Get client lang
	 *
	 * @return string
	 */
	public static function getLang(): ?string {
		return self::$currentLang;
	}

	/**
	 * Change client lang
	 *
	 * @param string $lang The new lang
	 */
	public static function setLang(string $lang): void {
		self::$currentLang = $lang;
	}

	/**
	 * Get package default lang
	 *
	 * @return string
	 */
	public static function getDefaultLang(): string {
		return self::$defaultLang;
	}

	/**
	 * Change default package lang
	 *
	 * @param string $lang The lang
	 */
	public static function setDefaultLang(string $lang) {
		self::$defaultLang = $lang;
	}

	public static function getCollator(): Collator {

		$lang = self::getLang();

		if(isset(self::$collator[$lang]) === FALSE) {
			self::$collator[$lang] = new Collator($lang);
		}

		return self::$collator[$lang];

	}

	/**
	 * Set the translation for a file
	 *
	 * @param string $file
	 * @param string $data
	 */
	public static function add(string $file, string $data) {

		if(isset(self::$languages[self::$currentLang]) === FALSE) {
			self::$languages[self::$currentLang] = [];
		}

		self::$languages[self::$currentLang] += $data;

		if(self::$debug) {

			if(isset(self::$files[self::$currentLang]) === FALSE) {
				self::$files[self::$currentLang] = [];
			}

			self::$files[self::$currentLang][$file] = $data;
		}

	}

	/**
	 * Get the translation for a file
	 * This method is very slow: do not use it in front office
	 *
	 * @param string $files
	 */
	public static function get(string $file): string {

		self::debug(TRUE);

		// Handle opcache.revalidate_freq > 0
		opcache_invalidate($file);

		require_once $file;

		self::debug(FALSE);

		return self::$files[self::$currentLang][$file];

	}

	/**
	 * Clean all loaded translations
	 */
	public static function clean() {
		self::$languages = [];
		self::$files = [];
	}

	/**
	 * Translate a text
	 *
	 * @param int $id Text id
	 * @param string $text The text to translate
	 * @param array $args Text arguments
	 */
	public static function s(int $id = NULL, string $text, $args = []): string {

		if(is_scalar($args) or $args === NULL) {
			$args = ['value' => $args];
		} else if(is_array($args) === FALSE and $args instanceof ArrayObject === FALSE) {
			throw new Exception('Invalid type for \'args\' ('.gettype($args).' given)');
		}

		return self::translate($id, $text, $args, NULL);

	}

	/**
	 * Translate a text depending of singular/plural
	 *	Special case for "ru_RU" due to the peculiar use of plural.
	 *	Here is the rule depending on $number:
	 *	Case 1 : 1, 21, 31, ..., 101, 121, ...1571 etc. (except 11, 111, 211, ...).
	 *	Case 2 : 2, 3, 4, 22, 23, 24, 32, 33, 34, ..., 102, 103, 104, 122, 123, 124 (every xx2, xx3, xx4. exception : x12, x13, x14).
	 *	Case 3 : 0, 5 – 20, 25 – 30, 35 – 40, ..., 105 – 120, 125 – 130, 135 – 140 (everything else).
	 *
	 * https://developer.mozilla.org/fr/Localisation_et_pluriels
	 *
	 * @param int $id Id
	 * @param string $singular Singular text
	 * @param string $plural Plural text
	 * @param string $number Number to select singular ou plural
	 * @param array $args Texts arguments
	 */
	public static function p(int $id = NULL, string $singular, string $plural, int|float $number, array $args = []): string {

		$type = self::getType(self::$currentLang, $number);

		$args += [
			'value' => $number
		];

		if($type === 0) {
			return self::translate($id, $singular, $args, 0);
		} else {
			return self::translate($id, $plural, $args, $type);
		}

	}

	protected static function translate(int $id = NULL, string $text, $args, int $type = NULL): string {

		self::$lastId = $id;

		// If text that we want is different than default lang, just get translation from current lang file
		if(self::$defaultLang !== self::$currentLang) {

			$getId = FALSE;
			if(isset(self::$languages[self::$currentLang]) === FALSE) {
				self::$languages[self::$currentLang] = [];
				$getId = TRUE;
			}

			if($getId or isset(self::$languages[self::$currentLang][$id]) === FALSE) {

				list($languageFile, $languagePackage) = self::getLanguageFile(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2));

				$getFile = FALSE;
				if(isset(self::$files[self::$currentLang]) === FALSE) {
					self::$files[self::$currentLang] = [];
					$getFile = TRUE;
				}

				if($getFile or isset(self::$files[self::$currentLang][$languageFile]) === FALSE) {

					self::$files[self::$currentLang][$languageFile] = [];

					if(is_file($languageFile)) {
						require_once $languageFile;
					}
				}
			}

			$language = self::$languages[self::$currentLang][$id] ?? $text;

			// Handle plural form case
			if($type !== NULL) {
				$language = $language[$type];
			}

		} else {
			$language = $text;
		}

		// If there is var to put on string
		$language = self::applyVariables($language, $args);

		if(self::$debug) {

			if($id !== NULL)  {

				$backTrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);

				list($file, $package) = self::getLanguageFile($backTrace);
				$file = substr($file, strlen(Package::getPath($package)) + strlen('/lang/'));

				if(isset(self::$debugList[$id]) === FALSE and self::hasErrorMessages($backTrace) === FALSE) {

					self::$debugList[$id] = [
						$file,
						$id,
						$package,
						$args,
					];

				}
			}

		}

		return $language;

	}

	protected static function hasErrorMessages(array $backTrace): bool {

		foreach($backTrace as $traceElement) {
			if(
				strpos($traceElement['function'], 'getError') !== FALSE or
				strpos($traceElement['function'], 'getMessage') !== FALSE
			) {
				return TRUE;
			}
		}

		return FALSE;
	}

	private static function getLanguageFile(array $backtrace): array {

		$viewFile = $backtrace[1]['file'];

		$languagePackage = Package::getPackageFromPath($viewFile);
		$languageRoot = Package::getPath($languagePackage);
		$languageFile = $languageRoot.'/lang/'.self::$currentLang.'/'.substr(substr($viewFile, strlen($languageRoot.'/view/')), 0, -8).'.m.php';

		return [$languageFile, $languagePackage];

	}

	/**
	 * Swap each var on string by its array value if exists
	 *
	 * @param string $string - The string to return
	 * @param string|array $arrayVar - Array of var on new tokenizer
	 * 				 system and list of var seperated
	 * 				 by comma on old one
	 *
	 */
	private static function applyVariables(string $string, $variables): string {

		return preg_replace_callback("/\{([a-z0-9]+?)\}|\<([a-z0-9]+)\>|\<\/([a-z0-9]+)\>/si", function($value) use ($string, $variables) {

			// {toto}
			if($value[1]) {
				return self::getVariable($value[1], $variables) ?? $value[1];
			}
			// <toto>
			else if($value[2]) {
				return self::getVariable($value[2], $variables) ?? '<'.$value[2].'>';
			}
			// </toto>
			else if($value[3]) {

				$tag = self::getVariable($value[3], $variables);

				if($tag !== NULL) {

					if(preg_match("/\<([a-z0-9]+)/", $tag, $result) > 0) {
						return '</'.$result[1].'>';
					} else {
						throw new Exception("Expected a HTML tag, '".$value[3]."' found");
					}

				} else {
					return '</'.$value[3].'>';
				}

			}

		}, $string);

	}

	public static function setVariables(array $variables): void {
		self::$variables = $variables + self::$variables;
	}

	public static function getVariable(string $key, $source = []): ?string {

		// Get each var found on string
		if($source instanceof ArrayObject) {
			$source = $source->getArrayCopy();
		}

		$source += self::$variables;

		if($source === []) {
			return NULL;
		}

		if(isset($source[$key])) {
			if(is_closure($source[$key])) {
				return ($source[$key])();
			} else {
				return $source[$key];
			}
		} else {
			return NULL;
		}

	}

	public static function getType(string $lang, float $floatNumber): int {

		$number = (int)$floatNumber;

		switch($lang) {

			case 'ar_AE' ;
				if($number < 3 or $number >= 11) {
					return 0;
				} else {
					return 1;
				}

			case 'ru_RU':
			case 'ru_UA':
				if($number%10 === 1 and $number%100 !== 11) {
					return 0;
				} else if($number%10 > 1 and $number%10 < 5 and $number%100 !==12 and $number%100 !==13 and $number%100 !==14) {
					return 1;
				} else {
					return 2;
				}

			case 'lt_LT':
				if($number%10 === 1 and $number !== 11) {
					return 0;
				} else if($number%10 === 0 or ($number%100 >= 10 and $number%100 <= 20)) {
					return 1;
				} else {
					return 2;
				}


			case 'pl_PL':
				if($number === 1) {
					return 0;
				} else if($number%10 > 1 and $number%10 < 5 and $number%100 !==12 and $number%100 !==13 and $number%100 !==14) {
					return 1;
				} else {
					return 2;
				}

			case 'ro_RO':
				if($number === 1) {
					return 0;
				} else if($number === 0 or ($number%100 >= 1 and $number%100 <= 19)) {
					return 1;
				} else {
					return 2;
				}

			case 'sk_SK':
			case 'cs_CZ':
				if($number === 1) {
					return 0;
				} else if($number >= 2 and $number <= 4) {
					return 1;
				} else {
					return 2;
				}

			case 'tr_TR':
				return 0;

			case 'fi_FI' :
				if((float)$floatNumber === 1.0) {
					return 0;
				} else {
					return 1;
				}

			case 'fr_FR' :
			case 'lv_LV':
				if($number < 2) {
					return 0;
				} else {
					return 1;
				}

			case 'sl_SI' :
				if(($number < 10 or $number > 100) and $number%10 === 1) {
					return 0;
				} else if(($number < 10 or $number > 100) and $number%10 === 2) {
					return 1;
				} else if(($number < 10 or $number > 100) and ($number%10 === 3 or $number%10 === 4)) {
					return 2;
				} else {
					return 3;
				}

			default :
				if($number === 1) {
					return 0;
				} else {
					return 1;
				}

		}

	}

	public static function countTypes(string $lang): int {

		switch($lang) {

			case 'sl_SI':
				return 4;

			case 'sk_SK':
			case 'ru_RU':
			case 'ru_UA':
			case 'pl_PL':
			case 'cs_CZ':
			case 'ro_RO':
			case 'lt_LT':
				return 3;

			case 'tr_TR' :
				return 1;

			default :
				return 2;

		}

	}

	public static function getLastId(): int {
		$id = self::$lastId;
		self::$lastId = NULL;
		return $id;
	}

	public static function getDebugList(): array {
		return self::$debugList;
	}

	public static function resetFiles() {
		self::$files = [];
	}

}
?>
