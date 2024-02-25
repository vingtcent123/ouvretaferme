<?php
namespace language;


/**
 * Export translation files
 */
class ExportLib {

	/**
	 * Contextual package
	 *
	 * @var \ReflectionPackage
	 */
	protected $package;

	/**
	 * Contextual pattern instance
	 *
	 * @var PatternLib
	 */
	protected $pattern;

	/**
	 * Exported messages
	 *
	 * @var array
	 */
	protected $export = [];

	/**
	 * Content of lastCustom file
	 *
	 * @var array
	 */
	protected $custom = [];

	/**
	 * Content of lastOld file
	 *
	 * @var array
	 */
	protected $old = [];

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
		$this->pattern = new PatternLib($package);

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
	 * Export new messages for a lang
	 *
	 * @param string $langSource
	 * @param string $lang
	 * @return array
	 */
	public function export(string $langSource, string $lang): array {

		$this->stats = [
			'files' => 0,
			'messages' => 0,
			'words' => 0,
		];

		$this->custom = $this->loadCustom();
		$this->old = $this->loadOld();

		$this->export = [];

		$this->package->browse('lang/'.$langSource, '.m.php', function($file) use($langSource, $lang) {

			$pathLangSource = $file->getPathname();

			if($this->pattern->match($pathLangSource, $lang) === FALSE) {
				return;
			}

			$pathLang = str_replace('/lang/'.$langSource.'/', '/lang/'.$lang.'/', $pathLangSource);

			// Array containing all the strings which are in the source lang but not in the selected lang
			$messages = array_diff_key(
				ParserLib::extractFromLang($pathLangSource),
				ParserLib::extractFromLang($pathLang)
			);

			foreach($messages as $id => $text) {

				// Singular
				if(is_string($text)) {

					$this->exportText($id, NULL, $pathLang, $pathLangSource, $text);

				}
				// Plural
				else {

					$types = \L::countTypes($lang);

					for($type = 0; $type < $types; $type++) {
						$this->exportText($id, $type, $pathLang, $pathLangSource, $text[$type] ?? $text[count($text) - 1]);
					}

				}

			}

			$this->stats['files']++;
			$this->stats['messages'] += count($messages);
			$this->stats['words'] += MessageLib::countWords($messages);

		});

		return $this->export;

	}

	/**
	 * Export a message
	 *
	 * @param int $id Message ID
	 * @param string $type Singular = NULL / Plural = [0..n]
	 * @param string $pathLang File name
	 * @param string $pathLangSource File name of source lang
	 * @param string $text Message text
	 */
	private function exportText(int $id, string $type, string $pathLang, string $pathLangSource, string $text) {

		$this->export[] = [
			'id' => $id,
			'type' => $type,
			'file' => $pathLang,
			'text' => $text,
			'custom' => $this->getCustom($id),
			'comments' => $this->getComments($id, $pathLang)
		] + $this->getOld($id, $type, $pathLang, $pathLangSource);

	}


	/**
	 * Get old text for the given ID
	 *
	 * @param int $id Message ID
	 * @param string $type Singular = NULL / Plural = [0..n]
	 * @param string $pathLang File name
	 * @param string $pathLangSource File name of source lang
	 */
	protected function getOld(int $id, string $type, string $pathLang, string $pathLangSource): array {

		$idOld = $id;

		while(isset($this->old[$idOld]) === FALSE) {

			if($idOld % 1000 === 0) {

				return [
					'old' => NULL,
					'oldSource' => NULL
				];

			}

			$idOld--;

		}

		// Get texts with old ID in lang files (both selected and source languages)
		$textOld = $this->getOldText($pathLang, $idOld);

		if(strpos($pathLangSource, '/lang/'.\L::getDefaultLang().'/') === FALSE) {
			$textOldSource = $this->getOldText($pathLangSource, $idOld);
		} else {
			$textOldSource = $this->old[$idOld];
		}

		// Handle plural case
		if($type !== NULL) {

			$textOld = $textOld[$type];

			// Source lang may have less plural form so we use the first one
			if(isset($textOldSource[$type]) === FALSE) {
				$textOldSource = end($textOldSource);
			} else {
				$textOldSource = $textOldSource[$type];
			}

		}

		return [
			'old' => $textOld,
			'oldSource' => $textOldSource
		];

	}

	/**
	 * Get text of a message
	 *
	 * @param string $pathLang
	 * @param int $id
	 * @return type
	 */
	private function getOldText(string $pathLang, int $id) {

		$messages = ParserLib::extractFromLang($pathLang);

		return $messages[$id] ?? NULL;

	}

	/**
	 * Load lastOld file avec returns its content
	 *
	 * @return array
	 */
	protected function loadOld(): array {

		$file = $this->package->getPath().'/lang/lastOld';

		if(is_file($file) === FALSE) {
			throw new \Exception("File '".$file."' can not be found. Please run 'language/prepare' first");
		}

		$content = file_get_contents($file);

		return json_decode($content, TRUE);

	}


	/**
	 * Get custom value for the given ID
	 *
	 * @param int $id
	 * @return array
	 */
	protected function getCustom($id) {

		return $this->custom[$id] ?? NULL;

	}

	/**
	 * Load lastCustom file avec returns its content
	 *
	 * @return array
	 */
	protected function loadCustom(): array {

		$file = $this->package->getPath().'/lang/lastCustom';

		if(is_file($file) === FALSE) {
			throw new \Exception("File '".$file."' can not be found. Please run 'language/prepare' first");
		}

		$content = file_get_contents($file);

		return json_decode($content, TRUE);

	}

	/**
	 * Get comments for the given ID and file
	 *
	 * @param int $id
	 * @param string $pathLang
	 */
	protected function getComments(int $id, string $pathLang): array {

		$pathView = MessageLib::pathToView($this->package, $pathLang);

		// TODO : doit sauter une fois qu'au prepare, les fichiers view obsolètes sont bien supprimés
		if(is_file($pathView) === FALSE) {
			return [];
		}

		$comments = [];
		$content = file_get_contents($pathView);

		// Search for a general comment
		if(preg_match('/^[^\{]+\/\*\!([^\!]+?)\!\*\//si', $content, $matches) > 0) {
			$comments[] = trim($matches[1]);
		}

		// Search for a specific comment
		if(preg_match('/\/\*\!([^\!]+?)\!\*\/\s*[\\]?L::[ps]\(\s*'.$id.'\s*\,/si', $content, $matches) > 0) {
			$comments[] = trim($matches[1]);
		}

		return $comments;

	}

}
?>
