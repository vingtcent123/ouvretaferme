<?php
namespace media;

/**
 * Manages photos
 *
 */
class GalleryLib extends MediaLib {

	public function clean(): bool {

		// Search in texts
		$cElement = \gallery\Photo::model()
			->select('hash')
			->recordset()
			->getCollection();

		foreach($cElement as $eElement) {
			$this->activeMedias([$eElement['hash']]);
		}

		return TRUE;

	}

}
?>
