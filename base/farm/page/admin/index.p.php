<?php
new Page(fn() => \user\ConnectionLib::getOnline()->checkIsAdmin())
	->match(
		['get', 'post'],
		'index', function($data) {

			$data->page = REQUEST('page', 'int');

			$data->search = new Search([
				'id' => GET('id'),
				'name' => GET('name'),
				'user' => GET('user'),
				'userId' => GET('userId'),
			], REQUEST('sort', default: 'id-'));

			list($data->cFarm, $data->nFarm) = \farm\AdminLib::getFarms($data->page, $data->search);

			throw new ViewAction($data);

	});
?>
