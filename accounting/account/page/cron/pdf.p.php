<?php
new Page()
	->cron('index', function($data) {

		$cCompanyCron = \company\CompanyCron::model()
			->select(\company\CompanyCron::getSelection() + ['farm' => ['id', 'name', 'legalName', 'siret']])
			->whereAction(\company\CompanyCronLib::FINANCIAL_YEAR_GENERATE_DOCUMENT)
			->getCollection();

		foreach($cCompanyCron as $eCompanyCron) {

			\company\CompanyLib::connectDatabase($eCompanyCron['farm']);

			try {

				\account\FinancialYearDocumentLib::generateAll($eCompanyCron['farm']);
				\company\CompanyCron::model()->delete($eCompanyCron);

			} catch(Exception $e) {

				trigger_error("Company Cron generate document error with #".$eCompanyCron['id'].' (farm #'.$eCompanyCron['farm']['id'].', '.$e->getMessage().')');

			}


		}


	}, interval: 'permanent@2');

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
