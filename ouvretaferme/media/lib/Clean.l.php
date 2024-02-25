<?php
namespace media;

class CleanLib {

	/**
	 * Checks if medias are used and update their status (ACTIVE, SEARCHING, DELETED);
	 *
	 */
	public static function check() {

		/*
		 * 1. clé de cache pour démarrer avec une date (sert de verrou sauf si la clé est vieille de plus de 3 jours)
		 * 2. mise à jour en SEARCHING
		 * 3. parcours de chaque type et mise en valid quand en trouve
		 * 4. mise en DELETED des SEARCHING restants
		 * 5. suppression de la clé de cache
		 */
		$startDate = Media::model()->now();

		$cacheKey = 'media-clean';

		if(LIME_ENV === 'dev') { // For debug purpose
			$cacheKey .= time();
		}

		$cacheTimeout = 86400 * 3; // 3 days

		// Lock for 3 days
		if(\Cache::db()->add($cacheKey, $startDate, $cacheTimeout)) {

			// Set medias in SEARCHING mode
			self::updateSearching();

			// Search for valid medias
			$result = self::searchValid();

			// Set unused medias in DELETED mode
			if($result) {
				self::updateDeleted();
			} else {
				self::restoreSearching();
			}

			// Unlock
			\Cache::db()->delete($cacheKey);

		}

	}

	protected static function updateSearching() {

		Media::model()
			->whereStatus(Media::ACTIVE)
			->where('updatedAt < NOW() - INTERVAL 5 DAY')
			->update([
				'status' => Media::SEARCHING
			]);

	}

	protected static function restoreSearching() {

		Media::model()
			->whereStatus(Media::SEARCHING)
			->update([
				'status' => Media::ACTIVE
			]);

	}

	protected static function searchValid(): bool {

		$images = \Setting::get('media\images');

		foreach($images as $image) {

			$libMedia = MediaLib::getInstance($image);

			if($libMedia->clean() === FALSE) {
				return FALSE;
			}

		}

		return TRUE;

	}

	protected static function updateDeleted() {

		// Update status of deleted medias
		// Only if they were is SEARCHING status
		Media::model()
			->whereStatus(Media::SEARCHING)
			->update([
				'updatedAt' => new \Sql('NOW()'),
				'status' => Media::DELETED
			]);

	}

	/**
	 * Destructs old medias
	 */
	public static function delete() {

		// Select only deleted medias
		// They must have been deleted 10 days ago
		$cMedia = Media::model()
			->select('id', 'status', 'type', 'hash')
			->whereStatus(Media::DELETED)
			->where('updatedAt < NOW() - INTERVAL 10 DAY')
			->getCollection();

		foreach($cMedia as $eMedia) {

			$url = $eMedia['type'].'/'.$eMedia['hash'].'.'.MediaUi::getExtension($eMedia['hash']);

			$formats = \Setting::get($eMedia['type'])['imageFormat'];

			foreach($formats as $format) {
				if(is_int($format)) {
					$formatString = $format;
				} else {
					$formatString = implode('x', $format);
				}
				$pathFormat = $eMedia['type'].'/'.$formatString.'/'.$eMedia['hash'].'.'.MediaUi::getExtension($eMedia['hash']);
				\Setting::get('media\mediaDriver')->delete($pathFormat);
			}

			\Setting::get('media\mediaDriver')->delete($url);
			\Setting::get('media\mediaDriver')->delete($url.'.txt');

			Media::model()->delete($eMedia);

		}

	}

}

?>
