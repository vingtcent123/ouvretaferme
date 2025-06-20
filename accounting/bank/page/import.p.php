<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		\Setting::set('main\viewBank', 'import');
	}
)
	->get('index', function($data) {

		// TODO Récupérer et sauvegarder dynamiquement
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$data->imports = \bank\ImportLib::formatCurrentFinancialYearImports($data->eFinancialYear);
		$data->cImport = \bank\ImportLib::getAll($data->eFinancialYear);

		throw new ViewAction($data);

	});

new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm = \farm\FarmLib::getById(INPUT('farm'))->validate('canManage');

		\Setting::set('main\viewBank', 'import');
	}
)
	->get('import', function($data) {

		throw new ViewAction($data);

	})
	->post('doImport', function($data) {

		$fw = new FailWatch();

		$result = \bank\ImportLib::importBankStatement($data->eFarm);

		if($fw->ok()) {
			throw new RedirectAction(\company\CompanyUi::urlBank($data->eFarm).'/import?success=bank:Import::'.$result);
		} else {
			throw new RedirectAction(\company\CompanyUi::urlBank($data->eFarm).'/import:import?error='.$fw->getLast());
		}

	});
?>
