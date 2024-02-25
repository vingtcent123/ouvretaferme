<?php
namespace gallery;

class PhotoLib extends PhotoCrud {

	public static function getPropertiesCreate(): array {
		return ['takenAt', 'farm', 'sequence', 'series', 'task', 'title' /* Le titre est vérifié à la fin car il dépend d'autres paramètres */];
	}

	public static function getPropertiesUpdate(): array {
		return ['title', 'takenAt'];
	}

	public static function getBySequence(\production\Sequence $eSequence): \Collection {

		return Photo::model()
			->select(Photo::model()->getProperties())
			->whereSequence($eSequence)
			->sort(['createdAt' => SORT_DESC])
			->getCollection();

	}

	public static function getBySeries(\series\Series $eSeries): \Collection {

		return Photo::model()
			->select(Photo::model()->getProperties())
			->whereSeries($eSeries)
			->sort(['createdAt' => SORT_DESC])
			->getCollection();

	}

	public static function getByTask(\series\Task $eTask): \Collection {

		return Photo::model()
			->select(Photo::model()->getProperties())
			->whereTask($eTask)
			->sort(['createdAt' => SORT_DESC])
			->getCollection();

	}

	public static function canCreate(string $hash): string {

		$eUser = \user\ConnectionLib::getOnline();

		return \media\Media::model()
			->select('hash')
			->whereUser($eUser)
			->whereType('gallery-photo')
			->whereHash($hash)
			->exists();

	}

	public static function create(Photo $e): void {

		$e->add([
			'sequence' => new \production\Sequence(),
			'series' => new \series\Series(),
			'task' => new \series\Task()
		]);

		$path = \storage\ImageLib::getBasePath().'/'.(new \media\GalleryUi())->getBasenameByHash($e['hash']);

		[$e['width'], $e['height']] = getimagesize($path);

		if($e['series']->notEmpty()) {
			$e['farm'] = $e['series']['farm'];
		} else if($e['task']->notEmpty()) {
			$e['farm'] = $e['task']['farm'];
			$e['series'] = $e['task']['series'];
		} else if($e['sequence']->notEmpty()) {
			$e['farm'] = $e['sequence']['farm'];
		} else {
			throw new \Exception('Missing series, sequence or farm in Photo');
		}

		Photo::model()->insert($e);

	}

}
?>
