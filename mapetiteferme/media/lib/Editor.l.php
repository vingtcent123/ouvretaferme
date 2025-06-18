<?php
namespace media;

/**
 * Manages photos
 *
 */
class EditorLib extends MediaLib {

	protected function getHashes(string $message) {

		$hashes = [];

		if(preg_match_all('/ xyz="([a-z0-9]+)"/si', $message, $matches) > 0) {
			$hashes = array_merge($hashes, $matches[1]);
		}

		return array_unique($hashes);

	}

}
?>
