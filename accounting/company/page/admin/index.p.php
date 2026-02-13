<?php
new Page(fn() => \user\ConnectionLib::getOnline()->checkIsAdmin())
	->get('index', function($data) {

		$data->nFarms = \company\AdminLib::countFarms();

		$data->search = new Search([], GET('sort'));
		$data->cFarm = \company\AdminLib::loadAccountingData($data->search);
		$data->cData = \data\DataLib::deferred();

		throw new ViewAction($data);

	});
