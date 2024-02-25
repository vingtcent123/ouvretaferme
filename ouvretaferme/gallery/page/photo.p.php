<?php
(new \gallery\PhotoPage())
	->read('index', fn($data) => throw new ViewAction($data))
	->update(fn($data) => throw new ViewAction($data))
	->doUpdate(function($data) {
		throw new BackAction();
	})
	->doDelete(fn($data) => throw new ViewAction($data));


(new \gallery\PhotoPage(function($data) {

		$data->hash = INPUT('hash');

		if(\gallery\PhotoLib::canCreate($data->hash) === FALSE) {
			throw new NotExpectedAction('Hash '.$data->hash);
		}

	}))
	->getCreateElement(function($data) {
		return new \gallery\Photo([
			'hash' => $data->hash
		]);
	})
	->doCreate(function($data) {
		throw new ViewAction($data);
	});
?>