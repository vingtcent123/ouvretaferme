<?php
namespace language;


/**
 * Get stats for language files
 */
class StatsLib {

	/**
	 * Count number of files, messages and words for the given package and lang
	 *
	 * @param \ReflectionPackage $package
	 * @param string $lang
	 */
	public static function count(\ReflectionPackage $package, string $lang): array {

		$nFiles = 0;
		$nMessages = 0;
		$nWords = 0;

		$libPattern = new PatternLib($package);

		$package->browse('lang/'.$lang, '.m.php', function($file) use($libPattern, $lang, &$nFiles, &$nMessages, &$nWords) {

			$path = $file->getPathname();

			// Check if path match pattern.cfg file for this lang
			if($libPattern->match($path, $lang) === FALSE) {
				return;
			}

			// Get messages
			$messages = ParserLib::extractFromLang($path);

			// Compute stats for messages
			$nFiles++;
			$nMessages += count($messages);

			foreach($messages as $texts) {

				$texts = (array)$texts;

				$nWords += array_reduce($texts, function($words, $text) {
					return $words + count(preg_split("/\s+/s", $text));
				}, 0);

			}

		});

		return [$nFiles, $nMessages, $nWords];

	}

}
?>
