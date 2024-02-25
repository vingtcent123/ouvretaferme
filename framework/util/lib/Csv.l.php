<?php
namespace util;

/**
 * Handle CSV files
 */
class CsvLib {

	/**
	 * Transform multiple rows of data into CSV.
	 * Example: array[[1,2,3],array[4,5,6],array[7,8,9]]
	 *
	 * @param array $lines The different lines, in an array of arrays
	 * @return string The CSV formated data
	 */
	public static function toCsv(array $lines = []): string {

		$stream = @fopen('php://temp', 'r+');

		foreach($lines as $line) {
			fputcsv($stream, $line, ';');
		}

		rewind($stream);

		$csv = stream_get_contents($stream);

		fclose($stream);

		return $csv;
	}

	/**
	 * Convert CSV file into an array
	 *
	 * @param string $path Path to CSV file
	 * @param string $separator CSV separator character
	 * @return array
	 */
	public static function parseCsv(string $path, string $separator = ';'): array {
	    
		$stream = fopen($path, 'r');

		if($stream === FALSE) {
			return [];
		}

		$lines = [];

		while(($line = fgetcsv($stream, 0, $separator, '"')) !== FALSE) {

			$lines[] = $line;

		}

		fclose($stream);

		return $lines;
	}

}

?>
