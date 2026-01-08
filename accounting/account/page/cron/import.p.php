<?php
new Page()
	->cron('index', function($data) {

		$cCompanyCron = \company\CompanyCron::model()
			->select(\company\CompanyCron::getSelection())
			->whereAction(\company\CompanyCronLib::FEC_IMPORT)
			->getCollection();

		foreach($cCompanyCron as $eCompanyCron) {

			\company\CompanyLib::connectDatabase($eCompanyCron['farm']);

			$eImport = \account\ImportLib::getById($eCompanyCron['element']);

			$imported = \account\ImportLib::treatImport($eCompanyCron['farm'], $eImport);

			if($imported) {
				\company\CompanyCron::model()->delete($eCompanyCron);
			}

		}

	}, interval: 'permanent@2');
?>
<?php
