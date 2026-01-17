<?php
new Page()
	->cron('clean', function($data) {

		$cFarm = \farm\Farm::model()
			->select(\farm\Farm::getSelection())
			->whereHasFinancialYears(TRUE)
			->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectDatabase($eFarm);

			\account\FinancialYearDocumentLib::clean();

		}

	}, interval: '0 5 * * *');
?>
