<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

	}
)
	->get('/banque/imports', function($data) {

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();


		$data->nSuggestion = \preaccounting\SuggestionLib::countWaitingByCashflow();

		$data->imports = \bank\ImportLib::formatCurrentFinancialYearImports($data->eFinancialYear);
		$data->cImport = \bank\ImportLib::getAll($data->eFinancialYear);

		throw new ViewAction($data);

	});

new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');

	}
)
	->get('/banque/imports:import', function($data) {

		throw new ViewAction($data);

	})
	->post('/banque/imports:doImport', function($data) {

		$fw = new FailWatch();

		$result = \bank\ImportLib::importBankStatement($data->eFarm);

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?success=bank:Import::'.$result);
		} else {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?error='.$fw->getLast());
		}

	});
?>
