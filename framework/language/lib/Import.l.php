<?php
namespace language;


/**
 * Import messages in lang files
 */
class ImportLib {

	/**
	 * Contextual package
	 *
	 * @var \ReflectionPackage
	 */
	protected $package;

	/**
	 * Stats for prepare action
	 *
	 * @var array
	 */
	protected $stats = [];

	/**
	 * Errors found
	 *
	 * @var array
	 */
	protected $errors = [];

	/**
	 * Warnings found
	 *
	 * @var array
	 */
	protected $warnings = [];

	/**
	 * Create a new instance
	 *
	 * @param ReflectionPackage $package The package
	 */
	public function __construct(\ReflectionPackage $package) {

		$this->package = $package;

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
	 * Return warnings found since the last call
	 *
	 * @return array
	 */
	public function getWarnings(): array {
		$warnings = $this->warnings;
		$this->warnings = [];
		return $warnings;
	}

	/**
	 * Import messages in lang files
	 *
	 * @param string $langSource
	 * @param string $lang
	 * @param array $messagesByPath
	 */
	public function import(string $langSource, string $lang, array $messagesByPath) {

		$this->stats = [
			'files' => 0,
			'messages' => 0,
			'words' => 0,
		];

		foreach($messagesByPath as $pathLang => $messagesCsv) {

			$pathLangSource = str_replace('/lang/'.$lang.'/', '/lang/'.$langSource.'/', $pathLang);

			// Get current messages
			$messagesLang = ParserLib::extractFromLang($pathLang);
			$messagesLangSource = ParserLib::extractFromLang($pathLangSource);

			$warningOrphans = $this->checkOrphans($pathLang, $messagesCsv, $messagesLangSource);

			if($warningOrphans) {
				$this->warnings['orphans'] = ($this->warnings['orphans'] ?? []) + $warningOrphans;
			}

			// Check is singular matchs singular and plural matchs plural
			$errorsNumber = $this->checkNumber($lang, $pathLang, $messagesCsv, $messagesLangSource);

			if($errorsNumber) {
				$this->errors['number'] = ($this->errors['number'] ?? []) + $errorsNumber;
			}

			// Check variables
			$errorsVariables = MessageLib::checkVariables($lang, $pathLang, $messagesCsv, $messagesLangSource);

			if($errorsVariables) {
				$this->errors['variables'] = ($this->errors['variables'] ?? []) + $errorsVariables;
			}

			// Check authorized tags
			$errorsTags = MessageLib::checkTags($pathLang, $messagesCsv);

			if($errorsTags) {
				$this->errors['tags'] = ($this->errors['tags'] ?? []) + $errorsTags;
			}

			if($errorsNumber === [] and $errorsVariables === [] and $errorsTags === []) {

				// Create lang file with both old and new messages
				$messages = $messagesCsv + $messagesLang;

				if($messages) {

					MessageLib::createLang($pathLang, $messages);

					$this->stats['files']++;
					$this->stats['messages'] += count($messages);
					$this->stats['words'] += MessageLib::countWords($messages);

				}

			 }

		}

	}

	/**
	 * Check if messages in CSV exist in source lang
	 *
	 * @param string $pathLang
	 * @param array $messagesCsv
	 * @param array $messagesLangSource
	 */
	private function checkOrphans(string $pathLang, array $messagesCsv, array $messagesLangSource): array {

		$warnings = [];

		$messagesDiff = array_diff_key($messagesCsv, $messagesLangSource);

		foreach($messagesDiff as $id => $text) {

			$warnings[$id] = [
				'path' => $pathLang,
				'text' => $text,
			];

		}

		return $warnings;

	}

	/**
	 * Check if CSV messages match source lang message (singular, plural...)
	 *
	 * @param string $lang
	 * @param string $pathLang
	 * @param array $messagesCsv
	 * @param array $messagesLangSource
	 * @return array
	 */
	private function checkNumber(string $lang, string $pathLang, array $messagesCsv, array $messagesLangSource): array {

		$errors = [];

		foreach($messagesLangSource as $id => $textLangSource) {

			if(isset($messagesCsv[$id]) === FALSE) {
				continue;
			}

			$textCsv = $messagesCsv[$id];

			// Singular
			if(is_string($textLangSource)) {

				if(is_string($textCsv) === FALSE) {

					$errors[$id] = [
						'path' => $pathLang,
						'type' => 'singular-expected',
						'text' => $textCsv,
					];

				}

			}
			// Plural
			else if(is_array($textLangSource)) {

				if(is_array($textCsv) === FALSE) {

					$errors[$id] = [
						'path' => $pathLang,
						'type' => 'plural-expected',
						'text' => $textCsv,
					];

				} else {

					$types = \L::countTypes($lang);

					for($type = 0; $type < $types; $type++) {

						if(isset($textCsv[$type]) === FALSE) {

							$errors[$id] = [
								'path' => $pathLang,
								'type' => 'plural-missing',
								'type' => $type,
							];

						}

					}

				}

			}

		}

		return $errors;

	}

}
?>
