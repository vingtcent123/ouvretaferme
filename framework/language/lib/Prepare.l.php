<?php
namespace language;


/**
 * Prepare PHP files for translation
 */
class PrepareLib {

	/**
	 * Contextual package
	 *
	 * @var \ReflectionPackage
	 */
	protected $package;

	/**
	 * Errors found
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Stats for prepare action
	 *
	 * @var array
	 */
	protected $stats = [];

	/**
	 * Create a new instance
	 *
	 * @param ReflectionPackage $package The package
	 */
	public function __construct(\ReflectionPackage $package) {

		$this->package = $package;

		$directory = $this->package->getPath().'/lang';

		if(is_dir($directory) === FALSE) {
			mkdir($directory, 0755);
		}

	}

	/**
	 * Return errors found since the last call
	 *
	 * @return array
	 */
	public function getErrors(): array {
		$errors = $this->errors;
		$this->errors = [];
		return $errors;
	}

	/**
	 * Return stats for the last prepare action
	 *
	 * @return array
	 */
	public function getStats(): array {
		return $this->stats;
	}

	/**
	 * Check messages in a package
	 * - Check messages integrity (duplicate entries)
	 * - Move messages
	 * - Delete unused lang files
	 * - Delete old messages in lang files
	 *
	 */
	public function prePrepare(): bool {

		// Check duplicate ids
		$duplicates = IdLib::checkIntegrity($this->package);

		if(empty($duplicates)) {

			// Move files
			$this->move();

			// Delete unused lang files
			$this->deleteOldFiles();

			// Delete old messages in lang files
			$this->deleteOldMessages();

			// Avoid side-effects
			clearstatcache();

			return TRUE;

		} else {
			$this->errors['duplicates'] = $duplicates;
			return FALSE;
		}

	}


	/**
	 * Move messages in the right lang files
	 * This is useful when messages are moved from a template to another one
	 *
	 */
	protected function move() {

		$orphansLang = [];
		$orphansView = [];

		// Search messages in lang files that do not match a view file
		$this->package->browse('lang/'.\L::getDefaultLang(), '.m.php', function($file) use(&$orphansLang) {

			$pathLang = $file->getPathname();
			$pathView = MessageLib::pathToView($this->package, $pathLang);

			if(is_file($pathView) === FALSE) {

				$orphansLang[$pathLang] = ParserLib::extractFromLang($pathLang);

			}

		}, '.m.php');

		// Search messages in view files that can not be found in their lang file
		$this->package->browse(['view/', 'ui/'], ['.v.php', '.u.php'], function($file) use(&$orphansLang, &$orphansView) {

			$pathView = $file->getPathname();
			$pathLang = MessageLib::pathToLang($this->package, $pathView, \L::getDefaultLang());

			$messagesView = ParserLib::extractFromView($pathView);

			if(is_file($pathLang)) {

				$messagesLang = ParserLib::extractFromLang($pathLang);

				$orphansView[$pathView] = array_diff_key($messagesView, $messagesLang);
				$orphansLang[$pathLang] = array_diff_key($messagesLang, $messagesView);

			} else {
				$orphansView[$pathView] = $messagesView;
			}

		});

		// Move translations
		if($orphansView) {
			$this->moveOrphans($orphansView, $orphansLang);
		}

	}

	private function moveOrphans(array $orphansView, array $orphansLang) {

		// Browse messages from view files that have no lang versions
		// If we can found lang messages in an another file, then we move them to the right file
		foreach($orphansView as $pathView => $messagesView) {

			while(list($id) = each($messagesView)) {

				// Try to find the same message in $orphansLang
				$pathViewBad = NULL;

				foreach($orphansLang as $pathLang => $messagesLang) {

					if(isset($messagesLang[$id])) {
						$pathViewBad = MessageLib::pathToView($this->package, $pathLang);
						break;
					}

				}

				if($pathViewBad !== NULL) {
					$this->moveOne($pathViewBad, $pathView, $id);
				}

			}

		}

	}

	/**
	 * For each lang, move the message identified by $id to the right file
	 *
	 * @param string $pathViewBad Source template
	 * @param string $pathView Destination template
	 * @param int $id ID to move
	 */
	private function moveOne(string $pathViewBad, string $pathView, int $id) {

		foreach($this->package->getLangs() as $lang) {

			$pathLang = MessageLib::pathToLang($this->package, $pathView, $lang);
			$pathLangBad = MessageLib::pathToLang($this->package, $pathViewBad, $lang);

			$messages = ParserLib::extractFromLang($pathLang);
			$messagesBad = ParserLib::extractFromLang($pathLangBad);

			// Move message if it exists
			if(isset($messagesBad[$id])) {

				$messages[$id] = $messagesBad[$id];
				ksort($messages);

				MessageLib::createLang($pathLang, $messages);

				// Delete old translation which has been moved
				unset($messagesBad[$id]);
				MessageLib::createLang($pathLangBad, $messagesBad);

			}

		}

	}

	/**
	 * Delete unused lang files
	 *
	 */
	protected function deleteOldFiles() {

		// Browse lang files and delete them if view file does not exist
		$this->package->browse('lang/', '.m.php', function($file) {

			$pathLang = $file->getPathname();

			// Maybe the file is already deleted as we may delete whole directories
			if(is_file($pathLang) === FALSE) {
				return;
			}

			$pathView = MessageLib::pathToView($this->package, $pathLang);

			// If the view file exists, nothing to do
			if(is_file($pathView)) {
				return;
			}

			// Check if view directory exists
			$directoryLang = dirname($pathLang);
			$directoryView = dirname($pathView);

			$directoryLangDelete = NULL;

			while($directoryView !== $this->package->getPath()) {

				if(is_dir($directoryView) === FALSE) {
					$directoryLangDelete = $directoryLang;
				}

				$directoryLang = dirname($directoryLang);
				$directoryView = dirname($directoryView);

			}

			// There is a whole directory to delete
			if($directoryLangDelete !== NULL) {
				exec('git rm '.$directoryLangDelete);
			} else {
				exec('git rm '.$pathLang);
			}

		});

	}

	/**
	 * Delete old messages
	 *
	 */
	protected function deleteOldMessages() {

		$langs = $this->package->getLangs();

		$this->package->browse(['view/', 'ui/'], ['.v.php', '.u.php'], function($file) use($langs) {

			$pathView = $file->getPathname();
			$messagesView = ParserLib::extractFromView($pathView);

			// Messages found in the view file, clean each lang file
			if($messagesView) {

				// Clean lang files
				foreach($langs as $lang) {

					$pathLang = MessageLib::pathToLang($this->package, $pathView, $lang);
					$messagesLang = ParserLib::extractFromLang($pathLang);

					$newMessagesLang = array_intersect_key($messagesLang, $messagesView);

					if($newMessagesLang !== $messagesLang) {
						MessageLib::createLang($pathLang, $newMessagesLang);
					}

				}

			}
			// No message found in the view file, delete all lang files
			else {

				foreach($langs as $lang) {

					$pathLang = MessageLib::pathToLang($this->package, $pathView, $lang);

					if(is_file($pathLang)) {
						exec('git rm '.$pathLang);
					}

				}

			}

		});

	}

	/**
	 * Prepare files
	 *
	 */
	public function prepare(): bool {

		$this->stats = [
			'files' => 0,
			'messages' => 0,
			'words' => 0,
		];

		$custom = [];
		$old = [];

		try {

			$this->package->browse(['view/', 'ui/'], ['.v.php', '.u.php'], function($file) use(&$custom, &$old) {

				$pathView = $file->getPathname();
				$pathLang = MessageLib::pathToLang($this->package, $pathView, \L::getDefaultLang());

				// Get the strings not translated
				$messagesToTranslate = ParserLib::extractToTranslate($this->package, $pathView, $pathLang);

				// Check unauthorized value on string to translate
				if($messagesToTranslate) {

					$errorsXml = MessageLib::checkXml($pathView, $messagesToTranslate);
					$errorsTags = MessageLib::checkTags($pathView, $messagesToTranslate);

					if($errorsXml) {
						if(isset($this->errors['xml']) === FALSE) {
							$this->errors['xml'] = [];
						}
						$this->errors['xml'] += $errorsXml;
					}

					if($errorsTags) {
						if(isset($this->errors['tags']) === FALSE) {
							$this->errors['tags'] = [];
						}
						$this->errors['tags'] += $errorsTags;
					}

					// Ignore the file if an error has been found
					if($errorsXml or $errorsTags) {
						return;
					}

				}

				$custom += ParserLib::getCustomMethods();

				// Convert view and put ID to each string that need to be translated
				ParserLib::giveIds($pathView, $messagesToTranslate);

				// Update the reflang file
				$langMessages = ParserLib::extractFromLang($pathLang);

				$newMessages = [];
				$newMessages += $messagesToTranslate;
				$newMessages += $langMessages;

				// Matchs keys with the content of the view
				$messagesFromView = ParserLib::extractFromView($pathView);
				$newMessages = array_intersect_key($newMessages, $messagesFromView);

				// Save unused messages
				$old += array_diff_key($langMessages, $newMessages);

				// Create the new lang file
				MessageLib::createLang($pathLang, $newMessages);

				// Save stats
				if($messagesToTranslate) {

					$this->stats['files']++;
					$this->stats['messages'] += count($messagesToTranslate);
					$this->stats['words'] += MessageLib::countWords($messagesToTranslate);

				}

			}, '.m.php');

			$this->saveCustom($custom);
			$this->saveOld($old);

			ParserLib::resetMatchId();

			return TRUE;

		}
		catch(\Exception $e) {
			$this->errors['exception'] = $e->getMessage();
			return FALSE;
		}

	}


	protected function saveCustom(array $custom) {

		$directory = $this->package->getPath().'/lang';
		$path = $directory.'/lastCustom';

		file_put_contents($path, json_encode($custom));

		$command = 'cd '.$directory.'; if [ `git status -s '.$path.'  2> /dev/null | grep "^?" | wc -l` -gt 0 ]; then git status -s 2> /dev/null | grep "^?" | cut -b 3-1000 | xargs git add --all; fi';

		exec($command);

	}

	protected function saveOld(array $old) {

		$directory = $this->package->getPath().'/lang';
		$path = $directory.'/lastOld';

		// Add existing entries
		if(is_file($path)) {

			$fileContent = file_get_contents($path);
			$fileOld = json_decode($fileContent, TRUE);

		} else {
			$fileOld = [];
		}

		$old += $fileOld;

		// Keep the 10 000 newest entries
		$old = array_slice($old, 0, 10000, TRUE);

		file_put_contents($path, json_encode($old));

		$command = 'cd '.$directory.'; if [ `git status -s '.$path.' 2> /dev/null | grep "^?" | wc -l` -gt 0 ]; then git status -s '.$path.' 2> /dev/null | grep "^?" | cut -b 3-1000 | xargs git add --all; fi';

		exec($command);

	}

}
?>
