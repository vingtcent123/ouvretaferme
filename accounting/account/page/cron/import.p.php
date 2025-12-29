<?php
new Page()
	->cron('index', function($data) {

		$cFarm = \farm\Farm::model()
			->select('id')
			->whereHasFinancialYears(TRUE)
			->getCollection();

		foreach($cFarm as $eFarm) {

			\company\CompanyLib::connectDatabase($eFarm);
			\account\ImportLib::manageImports($eFarm);

		}

	}, interval: 'permanent@2');
?>
<?php
