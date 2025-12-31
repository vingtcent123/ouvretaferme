<?php

new Page()
	->cron('index', function($data) {

		$cCompany = \company\Company::model()
			->select(\company\Company::getSelection())
			->whereReconciliation(\company\Company::WAITING)
			->getCollection();

		foreach($cCompany as $eCompany) {

			$updated = \company\Company::model()->update($eCompany, ['reconciliation' => \company\Company::PROCESSING]);

			if($updated === 1) {

				\company\CompanyLib::connectDatabase($eCompany['farm']);
				\preaccounting\SuggestionLib::calculateSuggestionsByFarm($eCompany['farm']);

				\company\Company::model()->update($eCompany, ['reconciliation' => \company\Company::DONE]);

			}

		}

	}, interval: 'permanent@2');
?>
