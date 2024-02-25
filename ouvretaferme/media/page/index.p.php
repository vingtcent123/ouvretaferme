<?php
(new Page(function($data) {

		\user\ConnectionLib::checkLogged();

		$data->type = POST('type', '?string');
		
		if($data->type === NULL) {
			throw new NotExpectedAction('No type');
		}

		$data->libMedia = \media\MediaLib::getInstance($data->type);

		if($data->libMedia === NULL) {
			throw new NotExpectedAction('Invalid type');
		}

		$data->eElement = $data->libMedia->buildElement();

		if($data->eElement->empty()) {

			$data->hash = POST('hash', '?string');

			$eUser = \user\ConnectionLib::getOnline();

			if(
				$data->hash !== NULL and
				\media\Media::model()
					->whereType($data->type)
					->whereHash($data->hash)
					->whereUser($eUser)
					->whereStatus('!=', \media\Media::DELETED)
					->exists() === FALSE
			) {
				throw new NotExpectedAction('Invalid hash');
			}

		} else {

			$hash = $data->eElement[$data->libMedia->getField()];

			if($hash !== NULL) {
				$data->hash = substr($hash, 0, 20);
			} else {
				$data->hash = NULL;
			}

		}

	}))
	->post('copy', function($data) {

		$basename = POST('basename', '?string');

		$data->libMedia->copy($data->eElement, $data->hash, $data->type, $basename);

		$data->color = $data->libMedia->getColor();
		$data->width = $data->libMedia->getWidth();
		$data->height = $data->libMedia->getHeight();
		$data->takenAt = $data->libMedia->getTakenAt();

		throw new ViewAction($data, path: ':put');

	})
	/**
	 * Updates an image
	 */
	->post('put', function($data) {

		$fileType = POST('filetype', '?int');

		if(post_exists('bounds')) {
			$bounds = (array)json_decode(POST('bounds'));
		} else {
			$bounds = [];
		}

		if(post_exists('basename')) {
			$put = $data->libMedia->putBasename($data->eElement, $data->hash, POST('basename', '?string'), $bounds, $fileType);
		} else if(isset($_FILES['file'])) {
			if(empty($_FILES['file']['tmp_name'])) {
				throw new StatusAction(400);
			}
			if($fileType === NULL) {
				$fileType = exif_imagetype($_FILES['file']['tmp_name']) or NULL;
			}
			$put = $data->libMedia->putFile($data->eElement, $data->hash, $_FILES['file']['tmp_name'], $bounds, $fileType);
		} else {
			$put = FALSE;
		}

		if($put) {

			$data->color = $data->libMedia->getColor();
			$data->width = $data->libMedia->getWidth();
			$data->height = $data->libMedia->getHeight();
			$data->takenAt = $data->libMedia->getTakenAt();

			throw new ViewAction($data);

		} else {
			throw new FailAction('media\imageInvalid');
		}

	})
	/**
	 * Crop an image
	 */
	->post('crop', function($data) {

		$basename = POST('basename', '?string');

		if(post_exists('bounds')) {
			$bounds = (array)json_decode(POST('bounds'));
		} else {
			$bounds = [];
		}

		$data->libMedia->crop($data->eElement, $basename, $bounds);

		$data->hash = \storage\ServerLib::parseBasename($basename)['hash'];

		$data->color = $data->libMedia->getColor();
		$data->width = $data->libMedia->getWidth();
		$data->height = $data->libMedia->getHeight();
		$data->takenAt = $data->libMedia->getTakenAt();

		throw new ViewAction($data, path: ':update');

	})
	/**
	 * Rotate an image
	 */
	->post('rotate', function($data) {

		$angle = POST('angle', 'int');

		$data->libMedia->rotate($data->eElement, $data->hash, $angle);

		$data->color = $data->libMedia->getColor();
		$data->width = $data->libMedia->getWidth();
		$data->height = $data->libMedia->getHeight();
		$data->takenAt = $data->libMedia->getTakenAt();

		throw new ViewAction($data, path: ':update');

	})
	/**
	 * Get crop information about an image
	 */
	->post('getCrop', function($data) {

		if($data->hash === NULL) {
			throw new NotExistsAction('hash must not be null');
		}

		$data->metadata = $data->libMedia->getMetadata($data->hash);

		throw new ViewAction($data);

	})
	/**
	 * Get crop information about an image
	 */
	->post('getCropPanel', function($data) {

		$data->width = POST('width', 'int');
		$data->height = POST('height', 'int');

		throw new ViewAction($data);

	})
	/**
	 * Deletes an image
	 */
	->post('delete', function($data) {

		if($data->eElement->empty()) {
			throw new NotExistsAction('Element is missing');
		}

		$data->libMedia->delete($data->eElement);

		throw new ViewAction($data);

	});
?>
