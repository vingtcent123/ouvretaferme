<?php
new Page()
	->cron('index', function($data) {

		$cCompanyCron = \company\CompanyCron::model()
			->select(\company\CompanyCron::getSelection())
			->whereAction(\company\CompanyCronLib::FINANCIAL_YEAR_GENERATE_DOCUMENT)
			->whereStatus(\company\CompanyCron::WAITING)
			->getCollection();

		foreach($cCompanyCron as $eCompanyCron) {

			\company\CompanyLib::connectDatabase($eCompanyCron['farm']);

			$updated = \company\CompanyCron::model()->update($eCompanyCron, ['status' => \company\CompanyCron::PROCESSING]);

			if($updated === 0) {
				continue;
			}

			\account\FinancialYearDocumentLib::generateWaiting($eCompanyCron['farm']);
			\company\CompanyCron::model()->delete($eCompanyCron);

		}

	}, interval: 'permanent@2')
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
