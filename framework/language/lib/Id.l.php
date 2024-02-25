<?php
namespace language;


/**
 * Handle ID for language files
 */
class IdLib {

	/**
	 * Check ID on each view to find duplicates
	 *
	 * @param ReflectionPackage $package Package
	 * @return array $duplicates Return array with message and number of duplicate ID found
	 */
	public static function checkIntegrity(\ReflectionPackage $package): array {

		$duplicates = [];

		$package->browse(['view/', 'ui/'], ['.v.php', '.u.php'], function($file) use(&$duplicates) {

			$path = $file->getPathname();

			$messages = ParserLib::extractFromView($path, FALSE);

			foreach($messages as list($id, $texts)) {

				if(isset($duplicates[$id]) === FALSE) {

					$duplicates[$id] = [
						'texts' => $texts,
						'count' => 1,
						'paths' => [$path]
					];

				} else {

					$duplicates[$id]['count']++;
					$duplicates[$id]['paths'][] = $path;

				}


			}

		});

		$duplicates = array_filter($duplicates, function($duplicate) {
			return ($duplicate['count'] > 1);
		});

		return $duplicates;

	}


	/**
	 * Get a new translation ID for a new message
	 * The counter is stored in /lang/lastId
	 *
	 * @param ReflectionPackage $package Package
	 * @return int
	 */
	public static function getNewId(\ReflectionPackage $package): int {

		$lastIdFile = $package->getPath().'/lang/lastId';

		if(is_file($lastIdFile)) {

			$lastId = (int)file_get_contents($lastIdFile) + 1;

		} else {

			$lastId = 1;

		}

		file_put_contents($lastIdFile, $lastId);

		return $lastId;

	}

}
?>
