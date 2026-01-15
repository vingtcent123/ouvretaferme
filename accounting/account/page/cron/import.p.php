<?php
new Page()
	->cron('index', function($data) {

		$cCompanyCron = \company\CompanyCron::model()
			->select(\company\CompanyCron::getSelection())
			->whereAction(\company\CompanyCronLib::FEC_IMPORT)
			->whereStatus(\company\CompanyCron::WAITING)
			->getCollection();

		foreach($cCompanyCron as $eCompanyCron) {

			\company\CompanyLib::connectDatabase($eCompanyCron['farm']);

			$updated = \company\CompanyCron::model()->update($eCompanyCron, ['status' => \company\CompanyCron::PROCESSING]);

			if($updated === 0) {
				continue;
			}

			$eImport = \account\ImportLib::getById($eCompanyCron['element']);

			try {

				$imported = \account\ImportLib::treatImport($eCompanyCron['farm'], $eImport);

				if($imported) {
					\company\CompanyCron::model()->delete($eCompanyCron);
				}

			} catch(Exception $e) {

				\company\CompanyCron::model()->update($eCompanyCron, ['status' => \company\CompanyCron::FAIL]);
				trigger_error("Company Cron fec import error with #".$eCompanyCron['id'].' (farm #'.$eCompanyCron['farm']['id'].', '.$e->getMessage().')');

			}

		}

	}, interval: 'permanent@2');
?>
<?php
