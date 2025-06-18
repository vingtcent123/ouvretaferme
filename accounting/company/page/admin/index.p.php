<?php
(new Page(fn() => Privilege::check('company\admin')))
	->match(
		['get', 'post'],
		'index', function($data) {

			$data->page = REQUEST('page', 'int');

			$data->search = new Search([
				'id' => GET('id'),
				'name' => GET('name'),
				'user' => GET('user'),
			], REQUEST('sort', default: 'id-'));

			list($data->cCompany, $data->nCompany) = \company\AdminLib::getCompanies($data->page, $data->search);

			throw new ViewAction($data);

	});
?>
