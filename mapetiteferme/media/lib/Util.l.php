<?php
namespace media;

class UtilLib {

	/**
	 * Cleans old media that needs to be deleted
	 * either reference === 0
	 * either status === deleted
	 */
	public static function clean() {

		$cMedia = Media::model()
			->select('id', 'status', 'updatedAt', 'type', 'hash')
			->whereStatus(Media::DELETED)
			->getCollection();

		foreach($cMedia as $eMedia) {

			$url = $eMedia['type'].'/'.$eMedia['hash']; // TODO OVH : il ne manque pas l'extension ?

			\Setting::get('media\mediaDriver')->delete($url);
			Media::model()->delete($eMedia);

		}

	}

	/**
	 * Generates a 19-characters hash
	 *
	 * @param string $end Optional end (from 0 to 6 characters)
	 * @return string
	 */
	public static function generateHash(string $end = ''): string {

		$endLength = strlen($end);

		if($endLength > 6) {
			throw new \Exception('Parameter \'end\' is too long (max length expected: 6, received: '.$end.')');
		}

		$hash = uniqid(); /* 13 caracters */

		$crcLength = 6 - $endLength;

		if($crcLength > 0) {
			$crc = sprintf('%0'.$crcLength.'x', mt_rand(0, 16 ** $crcLength - 1)); /* 6 caracters */
		} else {
			$crc = '';
		}

		return $hash.$crc.$end; /* 19 caracters */
	}

}

?>
