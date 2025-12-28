<?php
new Page(
)
	->get('/banque/imports', function($data) {

		$data->nSuggestion = \preaccounting\SuggestionLib::countWaitingByCashflow();

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

		$result = \bank\ImportLib::importBankStatement();

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?success=bank:Import::'.$result);
		} else {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?error='.$fw->getLast());
		}

	});
?>
