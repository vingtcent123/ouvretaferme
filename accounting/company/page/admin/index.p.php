<?php
new Page(fn() => \user\ConnectionLib::getOnline()->checkIsAdmin())
	->get('index', function($data) {

		$data->nFarms = \company\AdminLib::countFarms();

		$data->cFarm = \company\AdminLib::getFarms();

		$data->search = new Search([], GET('sort'));
		\company\AdminLib::loadAccountingData($data->cFarm, $data->search);

		throw new ViewAction($data);

	});
