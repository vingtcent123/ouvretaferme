<?php
namespace main;

class CsvLib {

	public static function upload(string $key, \Closure $callback): bool {

		if(isset($_FILES['csv']) === FALSE) {
			return FALSE;
		}

		$file = $_FILES['csv']['tmp_name'];

		if(empty($file)) {
			return FALSE;
		}

		// Vérification de la taille (max 1 Mo)
		if(filesize($file) > 1024 * 1024) {
			\Fail::log('csvSize');
			return FALSE;
		}

		$content = file_get_contents($file);

		$encoding = mb_detect_encoding($content, ['UTF-8', 'UTF-16', 'ISO-8859-1']);

		if(in_array($encoding, ['UTF-16', 'ISO-8859-1'])) {
			$content = iconv($encoding, 'UTF-8', $content);
		}

		$content = trim($content);

		file_put_contents($file, $content);

		$delimiter = self::detectDelimiter($file);
		$csv = \util\CsvLib::parseCsv($file, $delimiter);

		if($csv === []) {
			\Fail::log('main\csvSource');
			return FALSE;
		}

		$output = $callback($csv);

		if($output === NULL) {
			return FALSE;
		}

		\Cache::redis()->set($key, $output);

		return TRUE;

	}

	public static function detectDelimiter($csvFile) {

		$delimiters = [";" => 0, "," => 0, "\t" => 0, "|" => 0];

		$handle = fopen($csvFile, "r");
		$firstLine = fgets($handle);
		fclose($handle);
		foreach($delimiters as $delimiter => &$count) {
		  $count = count(str_getcsv($firstLine, $delimiter, escape: ''));
		}
		return array_search(max($delimiters), $delimiters);

	}

	public static function checkDateField(mixed &$value, string $error): ?string {

		if(
			$value !== NULL and
			\Filter::check('date', $value) === FALSE
		) {
			return $error;
		} else {
			return NULL;
		}

	}

	public static function formatFloat(mixed $value, int $precision = 2): ?float {

		return round((float)str_replace(',', '.', $value), $precision);

	}

	public static function formatDateField(mixed $value): ?string {

		if(
			$value !== NULL and
			preg_match('/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/', $value, $results)
		) {
			return $results[3].'-'.$results[2].'-'.$results[1];
		} else {
			return $value;
		}

	}

}
?>
