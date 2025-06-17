<?php
namespace language;


/**
 * Handle messages
 */
class MessageLib {

	/**
	 * Get the path of lang file from a view name
	 *
	 * @param ReflectionPackage package context
	 * @param string $pathView Path to view file
	 * @param string
	 */
	public static function pathToLang(\ReflectionPackage $package, string $pathView, string $lang): string {

		$pathPackage = $package->getPath();

		if(strpos($pathView, $pathPackage.'/view') !== FALSE) {
			return str_replace([$pathPackage, '.v.php'], [$pathPackage.'/lang/'.$lang, '.m.php'], $pathView);
		} else if(strpos($pathView, $pathPackage.'/ui') !== FALSE) {
			return str_replace([$pathPackage, '.u.php'], [$pathPackage.'/lang/'.$lang, '.m.php'], $pathView);
		} else {
			return $pathView;
		}

	}

	/**
	 * Get the path of view file from a lang file
	 *
	 * @param ReflectionPackage package context
	 * @param string $pathLang Path to lang file
	 * @param string
	 */
	public static function pathToView(\ReflectionPackage $package, string $pathLang): string {

		$pathPackage = $package->getPath();
		$pathCheck = substr($pathLang, 0, strlen($pathPackage) + 11);

		if(strpos($pathLang, $pathCheck.'/view') !== FALSE) {
			return str_replace([$pathCheck, '.m.php'], [$pathPackage, '.v.php'], $pathLang);
		} else if(strpos($pathLang, $pathCheck.'/ui') !== FALSE) {
			return str_replace([$pathCheck, '.m.php'], [$pathPackage, '.u.php'], $pathLang);
		} else {
			return $pathLang;
		}

	}

	/**
	 * Get the diff of two arrays of messages
	 *
	 * @param array $messages1
	 * @param array $messages2
	 * @return array $diff
	 */
	public static function diff(array $messages1, array $messages2): array {

		$diff = [];

		foreach($messages1 as $key => $message1) {

			// Plural form case
			if(is_array($message1)) {

				if(
					isset($messages2[$key]) and
					is_array($messages2[$key])
				) {

					$message2 = $messages2[$key];

					// if the sub array is different
					if($message1 !== $message2) {

						$diff[$key] = $message1;

					}

				// if it is a subArray in array1 but not in array2
				} else {
					$diff[$key] = $message1;
				}

			}
			// Singular form case
			else {

				if(isset($messages2[$key])) {

					$message2 = $messages2[$key];

					if($message1 !== $message2) {
						$diff[$key] = $message1;
					}

				} else {
					$diff[$key] = $message1;
				}

			}

		}

		return $diff;

	}

	/**
	 * Format a text for the given lang
	 *
	 * @param mixed $text The text issued from a message
	 * @param string $lang The lang
	 * @param bool $removeTabs Remove \t ?
	 * @return mixed
	 */
	public static function formatText(string $text, string $lang, bool $removeTabs = TRUE): string {

		$from = ['\\"', '\\\'', "\r", " ,", ",  ", "’"];
		$to = ['"', '\'', "",   ",",  ", ",  "'"];

		if($removeTabs) {
			$from[] = "\t";
			$to[] = "";
		}

		$text = str_replace($from, $to, $text);

		// Local typo rules
		if($lang !== 'fr_FR') {

			$text = str_replace(
				[' : ', ' ?', ' !', ' %'],
				[': ', '?', '!', '%'],
				$text
			);

		}

		return $text;

	}

	/**
	 * Remove \t from messages
	 *
	 * @param mixed $messages
	 *
	 * @return mixed
	 */
	public static function removeTabs(array $messages): array {

		foreach($messages as $id => $text) {

			if(is_array($text)) {

				foreach($text as $position => $pluralText) {
					$messages[$id][$position] = str_replace("\t", "", $pluralText);
				}

			} else {
				$messages[$id] = str_replace("\t", "", $text);
			}

		}

		return $messages;

	}


	/**
	 * Count the number of words in an array of messages
	 *
	 * @param $messages
	 * @return int
	 */
	public static function countWords(array $messages): int {

		$count = 0;

		foreach($messages as $text) {

			if(is_array($text)) {
				$count += self::countWords($text);
			} else {

				// Ponctuation signs should not be counted as words
				$cleanText = str_replace([',', ';', ':', '.', '?', '!', '(', ')'], '', $text);
				// Html tags should not be counted as words
				$cleanText = strip_tags($cleanText);
				// Trailling spaces should not be counted as words
				$cleanText = trim($cleanText);

				$count += count(preg_split("/\s+/s", $cleanText));
			}

		}

		return $count;

	}

	/**
	 * Create a .m.php lang file.
	 * All messages must be encoded with ->format() before calling this method
	 *
	 * @param string $pathLang the path to the lang file.
	 * @param array $messages all messages to print in the csv file.
	 */
	public static function createLang(string $pathLang, array $messages) {

		if(count($messages) === 0) {
			return;
		}

		ksort($messages);

		$content = "<?php\n";
		$content .= "L::add(__FILE__, [\n";

		foreach ($messages as $id => $text) {

			$content .= "\t".$id." => ";
			if(is_array($text)) {
				$content .= "[\n";
				foreach ($text as $index => $pluralText) {
					$content .= "\t\t".$index." => ";
					$content .= "\"".addcslashes($pluralText, '"')."\",\n";
				}
				$content .= "\t],\n";
			} else {
				$content .= "\"".addcslashes($text, '"')."\",\n";
			}

		}
		$content = substr($content, 0, strlen($content) - 2)."\n";

		$content .= "]);\n";
		$content .= "?>";

		if(is_dir(dirname($pathLang)) === FALSE) {
			@mkdir(dirname($pathLang), 0755, TRUE);
		}
		file_put_contents($pathLang, $content);

	}

	/**
	 * Check for errors
	 *
	 * @param string $pathView Path to view file
	 * @param string $messages
	 */
	public static function checkXml(string $pathView, array $messages): array {

		$errors = [];

		foreach($messages as $id => $text) {

			foreach((array)$text as $selectedText) {

				$selectedText = str_replace('&', '&amp;', $selectedText);
				$selectedText = preg_replace('/<([^a-zA-Z\/])/', '&lt;$1', $selectedText);
				$selectedText = preg_replace('/([^a-zA-Z\/\"\'])>/', '$1&gt;', $selectedText);

				if(isXml('<xml>'.$selectedText.'</xml>') === FALSE) {

					$errors[$id] = [
						'path' => $pathView,
						'text' => $selectedText
					];

					break;

				}

			}

		}

		return $errors;

	}

	/**
	 * Check for errors
	 *
	 * @param string $path Path to view or lang file
	 * @param string $messages
	 */
	public static function checkTags(string $path, array $messages): array {

		$errors = [];

		$tagsAllowed = \Setting::get('allowedTags');

		foreach($messages as $id => $text) {

			foreach((array)$text as $selectedText) {

				// Get HTML TAGS on current string to check
				$tags = self::getTags($selectedText);
				$tagsForbidden = array_diff($tags, $tagsAllowed);

				// Check if there is unauthorized value on string
				if($tagsForbidden) {

					$errors[$id] = [
						'path' => $path,
						'tags' => $tagsForbidden,
						'text' => $selectedText
					];

				}

			}

		}

		return $errors;

	}

	/**
	 * Check string with authorized value dictionnary
	 *
	 * @param string $text String to translate
	 * @return array $tags Array filled with HTML TAGS found on string
	 */
	private static function getTags(string $text): array {

		// array to put HTML found on string to translate
		$tags = [];

		// Pattern to check each HTML TAG on string
		// [<]		=>	Begining by <
		// [\/]{0,1}	=>	0 or 1 time /
		// [a-zA-Z]*	=>	A letter several times
		// [\/]{0,1}	=>	0 or 1 time \
		// [>]		=>	Ending by >
		$pattern ="/\<\/?([a-z]+)[^\>]*?\>/si";

		// Check pattenr on string to return each row where pattern found
		preg_match_all($pattern, $text, $tagsFound);

		// For each TAG found on string to check, then put on array to return
		foreach($tagsFound[1] as $tag) {

			// Put HTML tag on table identify by string ID
			$tags[] = $tag;

		}

		$tags = array_unique($tags);

		// Return array with all HTML TAGS if found
		return $tags;

	}

	/**
	 * Check if {varnam} in $messages match $messagesSource
	 *
	 * @param string $lang
	 * @param string $pathLang
	 * @param array $messages
	 * @param array $messagesSource
	 * @return array
	 *
	 */
	public static function checkVariables(string $lang, string $pathLang, array $messages, array $messagesSource): array {

		$errors = [];

		foreach($messages as $id => $text) {

			if(isset($messagesSource[$id])) {
				$textSource = $messagesSource[$id]; // The original sentence from view
			} else {
				continue;
			}

			// Singular
			if(is_string($text)) {

				$errors += self::checkVariableMessage($pathLang, $id, $text, $textSource);

			}
			// Plural
			else {

				for($type = 0; $type < count($text); $type++) {

					if(\L::countTypes($lang) === 1) {
						$typeSource = 0;
					} else {
						$typeSource = min(1, $type); // Handle multi-plural case
					}

					if(isset($text[$type]) and isset($textSource[$typeSource])) {
						$errors += self::checkVariableMessage($pathLang, $id, $text[$type], $textSource[$typeSource]);
					}

				}

			}

		}

		return $errors;

	}

	protected static function checkVariableMessage(string $pathLang, int $id, string $text, string $textSource): array {

		$enclosedVariables = self::getEnclosedVariables($text);
		$enclosedVariablesSource = self::getEnclosedVariables($textSource);

		// Check difference between the two texts
		$diffMissing = array_diff($enclosedVariablesSource, $enclosedVariables);
		$diffTooMuch = array_diff($enclosedVariables, $enclosedVariablesSource);

		if($diffMissing) {

			return [
				$id => [
					'path' => $pathLang,
					'difference' => implode(', ', $diffMissing),
					'text' => $text,
					'textSource' => $textSource
				]
			];

		} else if($diffTooMuch) {

			return [
				$id => [
					'path' => $pathLang,
					'difference' => implode(', ', $diffTooMuch),
					'text' => $text,
					'textSource' => $textSource
				]
			];

		} else {
			return [];
		}

	}

	/**
	 * Returns array with {var} found on string
	 *
	 * @param string $string String to process
	 * @return array Array of all enclosed var {var}
	 *
	 */
	private static function getEnclosedVariables(string $string): array {

		preg_match_all('/\{.+?\}|\<t\/?[a-z]+\>/si', $string, $results);
		return $results[0];

	}

}
?>
