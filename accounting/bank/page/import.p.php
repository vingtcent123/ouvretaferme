<?php
new Page(
)
	->get('/banque/imports', function($data) {

		$data->imports = \bank\ImportLib::formatCurrentFinancialYearImports($data->eFarm['eFinancialYear']);
		$data->cImport = \bank\ImportLib::getAll($data->eFarm['eFinancialYear']);

		$data->cImportLonely = \bank\ImportLib::getLonely($data->eFarm['eFinancialYear']);

		throw new ViewAction($data);

	});

new Page()
	->get('/banque/imports:import', function($data) {

		throw new ViewAction($data);

	})
	->post('/banque/imports:doImport', function($data) {

		$fw = new FailWatch();

		$eImport = \bank\ImportLib::importBankStatement($data->eFarm);

		if($fw->ko()) {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?error='.$fw->getLast());
		}

		if($eImport['account']->empty()) {

			$data->cBankAccount = \bank\BankAccountLib::getAll();
			$data->eImport = $eImport;

			throw new RedirectAction(\company\CompanyUi::urlBank($data->eFarm).'/import:update?id='.$eImport['id']);

		}

		if(count(($eImport['result']['imported']) ?? []) < 100) {

			\preaccounting\SuggestionLib::calculateSuggestionsByFarm($data->eFarm);
			\company\CompanyCronLib::addConfiguration($data->eFarm, \company\CompanyCronLib::RECONCILIATE, \company\CompanyCron::WAITING);

		}

		throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?success=bank:Import::'.$eImport['status']);

	});

new \bank\ImportPage()
	->update(function($data) {

		$data->cBankAccount = \bank\BankAccountLib::getAll();
		$data->eImport = $data->e;

		throw new ViewAction($data);

	}, validate: ['acceptUpdateAccount']);

new \bank\ImportPage()
	->applyElement(function($data, \bank\Import $e) {

		$e['newAccount'] = \bank\BankAccountLib::getById(POST('account'));

	})
	->doUpdateProperties('doUpdateAccount', ['account'], function($data) {

		\preaccounting\SuggestionLib::calculateSuggestionsByFarm($data->eFarm);
		\company\CompanyCronLib::addConfiguration($data->eFarm, \company\CompanyCronLib::RECONCILIATE, \company\CompanyCron::WAITING);

		throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?success=bank:Import::createdAndAccountSelected');

		}, ['acceptUpdateAccount'])
?>
