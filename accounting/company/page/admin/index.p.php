<?php
new Page(fn() => \user\ConnectionLib::getOnline()->checkIsAdmin())
	->get('index', function($data) {

		$data->nFarms = \company\AdminLib::countFarms();

		$data->page = REQUEST('page', 'int');

		list($data->cFarm, $data->nFarm) = \company\AdminLib::getFarms($data->page);

		\company\AdminLib::loadAccountingData($data->cFarm);


		throw new ViewAction($data);

	});
