<?php
new Page()
	->cron('index', function($data) {

		$cCompany = \company\Company::model()
			->select(\company\Company::getSelection())
			->whereFecImport(\company\Company::WAITING)
			->getCollection();

		foreach($cCompany as $eCompany) {

			$updated = \company\Company::model()->update($eCompany, ['fecImport' => \company\Company::PROCESSING]);

			if($updated === 1) {

				\company\CompanyLib::connectDatabase($eCompany['farm']);
				\account\ImportLib::manageImports($eCompany['farm']);

				\company\Company::model()->update($eCompany, ['fecImport' => \company\Company::DONE]);

			}

		}

	}, interval: 'permanent@2');
?>
<?php
