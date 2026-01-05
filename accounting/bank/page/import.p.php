<?php
new Page(
)
	->get('/banque/imports', function($data) {

		$data->imports = \bank\ImportLib::formatCurrentFinancialYearImports($data->eFarm['eFinancialYear']);
		$data->cImport = \bank\ImportLib::getAll($data->eFarm['eFinancialYear']);

		throw new ViewAction($data);

	});

new Page()
	->get('/banque/imports:import', function($data) {

		throw new ViewAction($data);

	})
	->post('/banque/imports:doImport', function($data) {

		$fw = new FailWatch();

		$eImport = \bank\ImportLib::importBankStatement($data->eFarm);

		if(count(($eImport['result']['imported']) ?? []) < 100) {
			$imported = TRUE;
			\preaccounting\SuggestionLib::calculateSuggestionsByFarm($data->eFarm);
		} else {
			$imported = FALSE;
		}

		if($fw->ok()) {

			if($imported === FALSE) {
				\company\CompanyCronLib::addConfiguration($data->eFarm, \company\CompanyCronLib::RECONCILIATE, \company\CompanyCron::WAITING);
			}

			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?success=bank:Import::'.$eImport['status']);
		} else {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?error='.$fw->getLast());
		}

	});
?>
