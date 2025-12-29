<?php

new Page()
	->cron('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select('id')
			->whereHasAccounting(TRUE)
			->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectDatabase($eFarm);
			\preaccounting\SuggestionLib::calculateSuggestionsByFarm($eFarm);

		}

	}, interval: 'permanent@2');
?>
