<?php
namespace language;

/**
 * Handle view files for translation tools
 *
 * @author jOberlin
 */
class ParserLib {

	/**
	 * Store custom methods
	 *
	 * @var array
	 */
	protected static array $custom = [];

	/**
	 * Get all messages from a view files
	 *
	 * @param string $path Path to the view file
	 * @param bool $associative TRUE for [ID => TEXTS, ID => TEXTS] array and FALSE for [[ID, TEXTS], [ID, TEXTS]] array (allow to see duplicates IDs
	 * @return array All messages from the view file
	 */
	public static function extractFromView(string $path, bool $associative = TRUE): array {

		$messages = [];

		$tokens = self::parseTokens($path);

		foreach($tokens as $key => $content) {

			if(is_array($content)) {

				if(
					self::getToken($tokens, $key) === [T_STRING, 'L'] and
					self::getToken($tokens, $key + 1) === [T_DOUBLE_COLON, '::']
				) {

					$form = self::getToken($tokens, $key + 2);

					// Get string on singular method's param'
					if($form === [T_STRING, 's']) {
						list($id, $text) = self::parseSingular($path, $tokens, $key);
					}
					// Get string on plurial method's param
					else if($form === [T_STRING, 'p']) {
						list($id, $text) = self::parsePlural($path, $tokens, $key);
					} else {
						continue;
					}

					if($associative) {
						$messages[$id] = $text;
					} else {
						$messages[] = [$id, $text];
					}

				}
			}

		}

		if($associative === FALSE) {
			$messages = array_unique($messages, SORT_REGULAR);
		}

		return $messages;

	}

	/**
	 * Get all messages from a lang file.
	 *
	 * @param string $path Path to the lang file.
	 * @return array All messages from the lang file.
	 */
	public static function extractFromLang(string $path): array {

		if(is_file($path)) {

			$messages = \L::get($path);
			ksort($messages);

			return $messages;

		} else {
			return [];
		}

	}

	protected static $matchPackage = NULL;
	protected static $matchDirectories = [];
	protected static $matchMinId = 0;

	/**
	 * Check these directories to get existing IDs
	 *
	 * @param ReflectionPackage $package Current package
	 * @param array $directories Répertoires où chercher des IDs déjà existants (par ordre croissant de version)
	 * @param int $position Valeur minimale du lastId pour chercher des IDs déjà existants
	 */
	public static function setMatchId(\ReflectionPackage $package, array $directories, int $position) {
		self::$matchPackage = $package;
		self::$matchDirectories = $directories;
		self::$matchMinId = $position;
	}

	/**
	 * Reset directories list used to match existing IDs
	 */
	public static function resetMatchId() {
		self::$matchPackage = NULL;
		self::$matchDirectories = [];
		self::$matchMinId = 0;
	}

	/**
	 * Export all s() and p() string on an array with ID
	 *
	 * @param ReflectionPackage $package Package
	 * @param string $pathView View file path
	 * @param string $pathLang Default language file path
	 *
	 */
	public static function extractToTranslate(\ReflectionPackage $package, string $pathView, string $pathLang): array {

		// Getting file content with token_get_all()
		$tokens = self::parseTokens($pathView);

		// Final array with all string to translate
		$messagesToTranslate = [];
		$messagesTranslated = [];

		// Var to false used on loop to said that the current string need to be translated
		$currentStringNew = FALSE;

		// Current handled message
		$currentString = [];

		// Is the current handled string a plural ?
		$currentStringPlural = FALSE;

		// Handle special functions
		$methods = \Setting::get('customMethods');
		$currentMethod = NULL;
		$currentMethodStart = NULL;
		$currentMethodArguments = NULL;

		self::$custom = [];

		foreach($tokens as $key => $content) {

			if(is_array($content)) {

				if($content[0] === T_WHITESPACE) {
					continue;
				}

				// Check for special methods
				foreach($methods as $name => $arguments) {

					// Cherche la présence de self::$name(
					if(
						self::getToken($tokens, $key) === [T_VARIABLE, '$this'] and
						self::getToken($tokens, $key + 1) === [T_OBJECT_OPERATOR, '->'] and
						self::getToken($tokens, $key + 2) === [T_STRING, $name] and
						self::getToken($tokens, $key + 3) === [NULL, '(']
					) {

						if($currentMethod !== NULL) {
							throw new \Exception("Invalid use of custom method '".$currentMethod."' in '".$pathView."' around '".self::getTokens($tokens, $key, 10)."'");
						}

						$currentMethod = $name;
						$currentMethodStart = $key + 4;
						$currentMethodArguments = $arguments;

					}

				}

				// It's a string to translate
				if(
					$content[1] === "s" and
					self::getToken($tokens, $key - 1) !== [T_DOUBLE_COLON, '::'] and
					self::getToken($tokens, $key - 2) !== [T_STRING, 'L']
				) {

					$currentStringNew = TRUE;
					$currentStringPlural = FALSE;

					$currentMethodStart = NULL;

				// It's a string with its plurial to translate
				} else if(
					$content[1] === "p" and
					self::getToken($tokens, $key - 1) !== [T_DOUBLE_COLON, '::'] and
					self::getToken($tokens, $key - 2) !== [T_STRING, 'L']
				) {

					$currentStringNew = TRUE;
					$currentStringPlural = TRUE;

					$currentMethodStart = NULL;

				// It's a translated string
				} else if(
					self::getToken($tokens, $key) === [T_STRING, 'L'] and
					self::getToken($tokens, $key + 1) === [T_DOUBLE_COLON, '::'] and
					self::getToken($tokens, $key + 2) === [T_STRING, 's']
				) {

					// Check if singular translated string is still the same
					list($id, $text) = self::parseSingular($pathView, $tokens, $key);

					$currentMethodStart = NULL;
					self::registerCustom($id, $currentMethod, $currentMethodArguments);

					$messagesTranslated[$id] = $text;

				// It's a translated string with its plural
				} else if(
					self::getToken($tokens, $key) === [T_STRING, 'L'] and
					self::getToken($tokens, $key + 1) === [T_DOUBLE_COLON, '::'] and
					self::getToken($tokens, $key + 2) === [T_STRING, 'p']
				) {

					// Check if plurial translated string is still the same
					list($id, $text) = self::parsePlural($pathView, $tokens, $key);

					$currentMethodStart = NULL;
					self::registerCustom($id, $currentMethod, $currentMethodArguments);

					$messagesTranslated[$id] = $text;


				} else {

					if($currentMethodStart !== NULL and $key >= $currentMethodStart and $content[0] !== T_WHITESPACE) {
						throw new \Exception("Invalid use of custom method '".$currentMethod."' in '".$pathView."' around '".self::getTokens($tokens, $key, 10)."'");
					}

					if($currentStringNew) {

						$content = self::getToken($tokens, $key);

						// It's a string between '' OR "" to translate
						if($content[0] === T_CONSTANT_ENCAPSED_STRING) {
							$currentString[] = self::parseString($content[1], $pathView);
						} else {
							throw new \Exception("Expected T_CONSTANT_ENCAPSED_STRING, found ".token_name($content[0])." in '".$pathView."' around '".self::getTokens($tokens, $key - 5, 15)."'");
						}

					}

				}

			// Pluriel non achevé
			} else if(
				($currentStringPlural === TRUE and count($currentString) === 1) and
				$content === ","
			) {

			} else if(
				($currentStringPlural === FALSE and count($currentString) === 1) or
				($currentStringPlural === TRUE and count($currentString) === 2)
			) {

				if(
					$currentStringNew === TRUE and
					($content === ")" or $content === ",")
				) {

					if(implode('', $currentString) !== '') {

						$matchId = self::getMatchId($pathView, $currentString);

						if($matchId !== NULL) {
							$id = $matchId;
						} else {
							$id = IdLib::getNewId($package) * 1000;
						}

						if($currentStringPlural) {
							$messagesToTranslate[$id] = $currentString;
						} else {
							$messagesToTranslate[$id] = $currentString[0];
						}

						self::registerCustom($id, $currentMethod, $currentMethodArguments);


					} else {
						throw new \Exception("Empty message in '".$pathView."' around '".self::getTokens($tokens, $key - 5, 15)."'");
					}

				}

				$currentString = [];
				$currentStringNew = FALSE;

			} else {

				if($currentMethodStart !== NULL and $key >= $currentMethodStart) {
					throw new \Exception("Invalid use of custom method '".$currentMethod."' in '".$pathView."' around '".self::getTokens($tokens, $key, 10)."'");
				}

			}

		}

		// Clean view messages
		$messagesToTranslate = MessageLib::removeTabs($messagesToTranslate);
		$messagesTranslated = MessageLib::removeTabs($messagesTranslated);

		// If translated string is different from source lang file, then add to translate once again
		if($messagesTranslated) {

			// Catch differences between lang file translated string and view
			$messagesChanged = MessageLib::diff(
				$messagesTranslated,
				ParserLib::extractFromLang($pathLang)
			);

			// Add translated string if there is a difference between view and its lang file
			foreach($messagesChanged as $id => $text) {

				$matchId = self::getMatchId($pathView, $text);

				if($matchId !== NULL) {
					$newId = $matchId;
				} else {

					if($id % 1000 !== 999) {
						$newId = $id + 1;
					} else {
						$newId = IdLib::getNewId($package) * 1000;
					}

				}

				$messagesToTranslate[$newId] = $text;

				// Replace old ID by new ID
				self::replaceId($pathView, $id, $newId);
				self::replaceCustom($id, $newId);

				// Format new text
				$newText = MessageLib::formatText($text, \L::getDefaultLang(), FALSE);

				if($text !== $newText) {
					self::replaceText($pathView, $newId, $newText);
				}

			}

		}

		return $messagesToTranslate;

	}



	/**
	 * Update a message in a view
	 *
	 * @param string $pathView View path
	 * @param int $id Message ID
	 * @param string $newText New text
	 */
	public static function replaceText(string $pathView, int $id, string $newText) {

		$tokens = self::parseTokens($pathView);
		$indentation = '';

		foreach($tokens as $key => $content) {

			// Get current indentation
			$character = self::getToken($tokens, $key);

			if($character[0] === T_WHITESPACE) {
				if(preg_match("/(\t+)[^\t]*$/si", $character[1], $result)) {
					$indentation = $result[1];
				}
			}

			if(is_array($content)) {

				if(
					self::getToken($tokens, $key) === [T_STRING, 'L'] and
					self::getToken($tokens, $key + 1) === [T_DOUBLE_COLON, '::']
				) {

					$form = self::getToken($tokens, $key + 2);

					if($form === [T_STRING, 's']) {

						// Get string on singular method's param'
						list($parsedId, $string, $parsedKey) = self::parseSingular($pathView, $tokens, $key);

						if($parsedId === $id) {
							self::formatNewText($tokens, $parsedKey, $newText, $indentation);
						}

					} else if($form === [T_STRING, 'p']) {

						// Get string on plurial method's param
						list($parsedId, $strings, $parsedKeys) = self::parsePlural($pathView, $tokens, $key);

						if(is_array($newText) === FALSE or count($parsedKeys) !== count($newText)) {
							throw new Exception("Expected plural form for new text");
						}

						if($parsedId === $id) {

							foreach($newText as $position => $newPluralText) {
								self::formatNewText($tokens, $parsedKeys[$position], $newPluralText, $indentation);
							}

						}

					}

				}
			}

		}

		// Update  view file
		$file = fopen($pathView, 'wr');

		foreach($tokens as $content) {
			if(is_array($content)){
				fwrite($file, $content[1]);
			} else {
				fwrite($file, $content);
			}
		}

		fclose($file);

	}

	/**
	 * Change an ID for a new one in a view file
	 *
	 * @param string $pathView
	 * @param int $id Old ID
	 * @param int $newId New ID
	 *
	 */
	public static function replaceId(string $pathView, int $id, int $newId) {

		//Update the Id in the view file
		$tokens = self::parseTokens($pathView);

		foreach($tokens as $key => $content) {

			if(is_array($content)) {

				if(
					$content[1] === (string)$id and
					self::getToken($tokens, $key, -1) === [NULL, '('] and
					(
						self::getToken($tokens, $key, -2) === [T_STRING, 's'] or
						self::getToken($tokens, $key, -2) === [T_STRING, 'p']
					) and
					self::getToken($tokens, $key, -3) === [T_DOUBLE_COLON, '::']
				) {

					$tokens[$key][1] = $newId;

				}

			}
		}


		$file = fopen($pathView, 'wr');

		foreach($tokens as $content) {
			if(is_array($content)){
				fwrite($file, $content[1]);
			} else {
				fwrite($file, $content);
			}
		}

		fclose($file);

	}

	protected static function getMatchId(string $pathView, string $currentString) {

		if(self::$matchDirectories === []) {
			return;
		}

		$currentString = (array)$currentString;

		$directories = array_reverse(self::$matchDirectories);

		$pathLang = MessageLib::pathToLang(self::$matchPackage, $pathView, \L::getDefaultLang());
		$fileLang = substr($pathLang, strlen(self::$matchPackage->getPath()) + 1);

		foreach($directories as $directory) {

			$pathLangCheck = $directory.'/'.$fileLang;

			$messages = ParserLib::extractFromLang($pathLangCheck);

			foreach($messages as $id => $message) {

				// On ne prend que les clés récentes pour éviter des conflits avec des chaines qui auraient changé de fichier
				if($id < self::$matchMinId) {
					continue;
				}

				if(
					(count($currentString) === 2 and $currentString === $message) or // Plural
					(count($currentString) === 1 and $currentString[0] === $message) // Singular
				) {
					return $id;
				}

			}

		}

	}

	/**
	 * Return all custom methods
	 *
	 * @return array
	 */
	public static function getCustomMethods(): array {
		return self::$custom;
	}

	/**
	 * Register a custom method
	 *
	 * @param int $key
	 * @param string $currentMethod
	 * @param array $currentMethodArguments
	 */
	protected static function registerCustom(int $key, string &$currentMethod, array &$currentMethodArguments) {

		if($currentMethod === NULL) {
			return;
		}

		$value = array_shift($currentMethodArguments);

		self::$custom[$key] = [$currentMethod => $value];

		if(empty($currentMethodArguments)) {
			$currentMethod = NULL;
		}
	}

	/**
	 * Change ID in a registered custom method
	 *
	 * @param int $oldId
	 * @param int $newId
	 */
	protected static function replaceCustom(int $oldId, int $newId) {

		if(isset(self::$custom[$oldId])) {

			self::$custom[$newId] = self::$custom[$oldId];
			unset(self::$custom[$oldId]);

		}

	}


	/**
	 * Convert s() to L::s(), p() to L::p() and add ID in view file
	 *
	 * @param string $pathView The full path to the view file
	 * @param array $messages Array with newID => "string to translate"
	 * @return
	 */
	public static function giveIds(string $pathView, array $messages) {

		$pathType = \Package::getTypeFromPath($pathView);

		// Getting file content with token_get_all()
		$tokens = self::parseTokens($pathView);

		// Has namespace ?
		$hasNamespace = in_array($pathType, ['lib', 'ui']) ? '\\' : '';

		// Variables used in the loop to store data about the current string that need to be translated
		$stringExpected = 0;
		$stringGot = [];
		$viewKey = null;

		foreach($tokens as $key => $content) {

			if(is_array($content)) {

				// It's a string to translate
				if($content[1] === "s") {

					if(self::getToken($tokens, $key, -1) !== [T_DOUBLE_COLON, '::']) {

						$tokens[$key][1] = $hasNamespace.'L::s';
						$viewKey = NULL;
						$stringExpected = 1;
						$stringGot = [];

					}

				// It's a string with its plurial to translate
				} else if($content[1] === "p") {

					if(self::getToken($tokens, $key, -1) !== [T_DOUBLE_COLON, '::']) {

						$tokens[$key][1] = $hasNamespace.'L::p';
						$viewKey = NULL;
						$stringExpected = 2;
						$stringGot = [];

					}

				} else if($stringExpected === 0) {
					continue;
				} else if(count($stringGot) < $stringExpected) {

					// It's a string between '' OR "" to translate
					if($content[0] === T_CONSTANT_ENCAPSED_STRING) {

						$stringGot[] = self::parseString($content[1], $pathView);

						if($viewKey === NULL) {
							$viewKey = $key;
						}

					}

				} else {
					throw new \Exception("Wrong argument count in file '".$pathView."' around '".implode("', '", $stringGot)."' and '".self::getTokens($tokens, $key, 10)."'");
				}

			} else if($content === ',' and count($stringGot) < $stringExpected) {
					continue;
			} else if($stringExpected > 0) {

				if(($content === ")" or $content === ",") and count($stringGot) === $stringExpected) {

					$stringGot = MessageLib::removeTabs($stringGot);

					$idLanguage = NULL;

					foreach($messages as $key => $value) {

						if(
							(is_string($value) and implode('', $stringGot) === $value) or
							(is_array($value) and $stringGot === $value)
						) {
							$idLanguage = $key;

							// We do not use the same ID for two same texts if the text is called in a custom method.
							if(isset(self::$custom[$key])) {
								unset($messages[$key]);
								break;
							}

						}

					}

					if($idLanguage === NULL) {
						throw new \Exception("Can not find ID for message '".implode(', ', $stringGot)."' in file '".$pathView."' around '".self::getTokens($tokens, $key, 10)."'");
					}

					$tokens[$viewKey][1] = $idLanguage.', '.$tokens[$viewKey][1];

					$stringExpected = 0;
					$stringGot = [];
					$viewKey = NULL;

				}

			}

		}

		// Update  view file
		if(is_writable($pathView)) {

			$file = fopen($pathView, 'wr');

			foreach($tokens as $content) {
				if(is_array($content)){
					fwrite($file, $content[1]);
				} else {
					fwrite($file, $content);
				}
			}

			fclose($file);

		}

		// Format messages
		foreach($messages as $id => $text) {

			$newText = MessageLib::formatText($text, \L::getDefaultLang(), FALSE);

			if($text !== $newText) {
				self::replaceText($pathView, $id, $newText);
			}

		}

	}


	/**
	 * Parse a L::s() string
	 *
	 * @param string $pathView View file
	 * @param array $tokens
	 * @param int $currentKey The current key, while the process is running
	 *
	 * @return array Array with ID and string
	 *
	 */
	private static function parseSingular(string $pathView, array $tokens, int $currentKey): array {

		$count = 0;

		$id = NULL;
		$string = NULL;
		$key = NULL;

		while($count < 1) {

			// If it's an ID (305 : T_LNUMBER)
			if($tokens[$currentKey][0] === T_LNUMBER) {

				// Put ID into an array and then its translated string
				$id = (int)$tokens[$currentKey][1];

			// else If it's a string (315 : T_CONSTANT_ENCAPSED_STRING)
			} else if($tokens[$currentKey][0] === T_CONSTANT_ENCAPSED_STRING) {

				$next = self::getToken($tokens, $currentKey + 1);

				if($next[1] !== ')' and $next[1] !== ',') {
					throw new \Exception("Invalid call of \L::s() found in '".$pathView."' around '".$tokens[$currentKey][1]."'");
				}

				$string = self::parseString($tokens[$currentKey][1], $pathView);
				$key = $currentKey;

				$count++;

			}

			$currentKey++;

		}

		// Make the final array with each ID and translated string
		if($id and $string) {
			return [$id, $string, $key];
		} else {
			throw new \Exception("Invalid call of \L::s() found in '".$pathView."' around '".self::getTokens($tokens, $currentKey, 10)."'");
		}

	}

	/**
	 * Parse a L::p() string
	 *
	 * @param string $pathView View file
	 * @param array $tokens
	 * @param int $currentKey The current key, while the process is running
	 *
	 * @return array Array with ID and strings
	 *
	 */
	private static function parsePlural(string $pathView, array $tokens, int $currentKey): array {

		$count = 0;

		$id = NULL;
		$strings = [];
		$keys = [];

		while($count < 2) {

			// If it's a ID (305 : T_LNUMBER)
			if($tokens[$currentKey][0] === T_LNUMBER) {

				if($id === NULL) {

					// Put ID into an array and then its translated string
					$id = (int)$tokens[$currentKey][1];

				}

			// else If it's a string (315 : T_CONSTANT_ENCAPSED_STRING)
			} else if($tokens[$currentKey][0] === T_CONSTANT_ENCAPSED_STRING) {

				$next = self::getToken($tokens, $currentKey + 1);

				if($next[1] !== ')' and $next[1] !== ',') {
					throw new \Exception("Invalid call of \L::p() found in '".$pathView."' around '".$tokens[$currentKey][1]."'");
				}

				$count++;

				// Put string into an array
				$strings[] = self::parseString($tokens[$currentKey][1], $pathView);
				$keys[] = $currentKey;

			}

			$currentKey++;

		}

		if($id and $strings) {
			return [$id, $strings, $keys];
		} else {
			throw new \Exception("Could not find regular \L::p() in '".$pathView."'");
		}

	}

	/**
	 * Return a  T_CONSTANT_ENCAPSED_STRING as a strings
	 *
	 * @param type $content
	 * @param type $pathView
	 * @return string
	 */
	protected static function parseString(string $content, string $pathView): string {

		if(preg_match("/\\\\\\\\[nrt]/si", $content) > 0) {
			throw new \Exception("String '".$content."' must not contain \\\\n, \\\\r or \\\\t characters in '".$pathView."'");
		}

		return eval('return '.$content.';');

	}

	/**
	 * Get a list of tokens starting from a position
	 *
	 * @param array $tokens
	 * @param int $position Starting position
	 * @param int $number Number of tokens to return
	 * @return array
	 */
	private static function getTokens(array $tokens, int $position, int $number): string {

		$content = '';

		for($i = $position; $i < $position + $number; $i++) {

			if(isset($tokens[$i])) {
				if(is_array($tokens[$i])) {
					$content .= $tokens[$i][1];
				} else {
					$content .= $tokens[$i];
				}
			}

		}

		return $content;

	}

	/**
	 * Get token that match the given position
	 *
	 * @param array $tokens
	 * @param int $position
	 * @param int $relative Get a token forward or backward and ignores whitespaces
	 * @return array A token (ie: [T_STRING, 'L'])
	 */
	private static function getToken(array $tokens, int $position, int $relative = 0): array {

		if(isset($tokens[$position]) === FALSE) {
			return [NULL, NULL];
		}

		if($relative === 0) {

			$value = $tokens[$position];
			return is_string($value) ? [NULL, $value] : [$value[0], $value[1]];

		} else {

			$increment = ($relative > 0) ? 1 : -1;
			$position += $increment;

			while(isset($tokens[$position])) {

				$value = $tokens[$position];

				if(is_array($value) and $value[0] === T_WHITESPACE) {
					$position += $increment;
				} else {
					return self::getToken($tokens, $position, $relative - $increment);
				}

			}

		}

		return [NULL, NULL];

	}

	/**
	 * Getting tokens of view file
	 *
	 * @param string $pathView The full path to the view file
	 */
	private static function parseTokens(string $pathView): array {

		// Get content of a view file
		$content = file_get_contents($pathView);

		// Getting file content with token_get_all()
		$tokens = token_get_all($content);

		return $tokens;

	}


	/**
	 * Format a new text that replaces an old one in a view file
	 *
	 * @param array $tokens
	 * @param int $key
	 * @param string $newText
	 * @param string $indentation
	 */
	private static function formatNewText(array &$tokens, int $key, string $newText, string $indentation) {

		$newText = str_replace("\r", "", $newText);
		$newText = str_replace("\n", "\n".$indentation, $newText);

		$text = $tokens[$key][1];
		$separator = $text[0];

		switch($separator) {

			case '"' :
				$escape = '\\"';
				break;

			case '\'' :
				$escape = '\\\'';
				break;

		}

		$tokens[$key][1] = $separator.addcslashes($newText, $escape).$separator;

	}

}
?>
