<?php

new Page()
	->cron('index', function($data) {

		$cCompanyCron = \company\CompanyCron::model()
			->select(\company\CompanyCron::getSelection())
			->whereAction(\company\CompanyCronLib::RECONCILIATE)
			->whereStatus(\company\CompanyCron::WAITING)
			->getCollection();

		foreach($cCompanyCron as $eCompanyCron) {

			$updated = \company\CompanyCron::model()->update($eCompanyCron, ['status' => \company\CompanyCron::PROCESSING]);

			if($updated === 0) {
				continue;
			}

			try {

				\company\CompanyLib::connectDatabase($eCompanyCron['farm']);
				\preaccounting\SuggestionLib::calculateSuggestionsByFarm($eCompanyCron['farm']);

				\company\CompanyCron::model()->delete($eCompanyCron);

			} catch(Exception $e) {

				\company\CompanyCron::model()->update($eCompanyCron, ['status' => \company\CompanyCron::FAIL]);
				trigger_error("Company Cron fec import error with #".$eCompanyCron['id'].' (farm #'.$eCompanyCron['farm']['id'].', '.$e->getMessage().')');

			}

		}

	}, interval: 'permanent@2');
?>
