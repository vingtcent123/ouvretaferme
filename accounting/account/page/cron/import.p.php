<?php
new Page()
	->cron('index', function($data) {

		$cCompanyCron = \company\CompanyCron::model()
			->select(\company\CompanyCron::getSelection())
			->whereAction(\company\CompanyCronLib::FEC_IMPORT)
			->whereStatus(\company\CompanyCron::WAITING)
			->getCollection();

		foreach($cCompanyCron as $eCompanyCron) {

			$updated = \company\CompanyCron::model()->update($eCompanyCron, ['status' => \company\CompanyCron::PROCESSING]);

			if($updated === 1) {

				\company\CompanyLib::connectDatabase($eCompanyCron['farm']);
				\account\ImportLib::manageImports($eCompanyCron['farm']);

				\company\CompanyCron::model()->delete($eCompanyCron);

			}

		}

	}, interval: 'permanent@2');
?>
<?php
