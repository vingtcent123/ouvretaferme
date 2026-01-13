<?php
new Page()
	->cron('index', function($data) {

		$cCompanyCron = \company\CompanyCron::model()
			->select(\company\CompanyCron::getSelection() + ['farm' => ['id', 'name', 'legalName', 'siret']])
			->whereAction('IN', [\company\CompanyCronLib::FINANCIAL_YEAR_GENERATE_OPENING, \company\CompanyCronLib::FINANCIAL_YEAR_GENERATE_CLOSING])
			->getCollection();

		foreach($cCompanyCron as $eCompanyCron) {

			\company\CompanyLib::connectDatabase($eCompanyCron['farm']);

			if($eCompanyCron['action'] === \company\CompanyCronLib::FINANCIAL_YEAR_GENERATE_OPENING) {
				\account\FinancialYearLib::generateOpenWaiting($eCompanyCron['farm']);
			} else {
				\account\FinancialYearLib::generateCloseWaiting($eCompanyCron['farm']);
			}

			\company\CompanyCron::model()->delete($eCompanyCron);

		}


	}, interval: 'permanent@2');
?>
