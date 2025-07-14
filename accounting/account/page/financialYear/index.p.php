<?php
new Page()
	->get('index', function($data) {

		$data->eFarm->validate('canManage');

		$data->cFinancialYear = \account\FinancialYearLib::getAll();
		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();

		throw new ViewAction($data);

	});

new \account\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');
	}
)
	->create(function($data) {

		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();
		if($data->cFinancialYearOpen->count() >= 2) {
			throw new NotExpectedAction('Cannot create a new financial year as there are already '.$data->cFinancialYearOpen->count().' financial years open');
		}

		$nextDates = \account\FinancialYearLib::getNextFinancialYearDates();
		$data->e['startDate'] = $nextDates['startDate'];
		$data->e['endDate'] = $nextDates['endDate'];

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();
		if($data->cFinancialYearOpen->count() >= 2) {
			throw new NotExpectedAction('Cannot create a new financial year as there are already '.$data->cFinancialYearOpen->count().' financial years open');
		}

		throw new ReloadAction('account', 'FinancialYear::created');

	})
	->update(function($data) {

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {

		throw new ReloadAction('account', 'FinancialYear::updated');

	})
	->read('close', function($data) {

		$data->e['vatData'] = \account\FinancialYearLib::getDataCheckForOpenFinancialYears($data->e);

		$data->cFinancialYearOpen = \account\FinancialYearLib::getOpenFinancialYears();
		$search = new Search(['financialYear' => $data->e]);
		$data->cOperationCharges = \journal\OperationLib::getAllChargesForClosing($search);
		\journal\DeferredChargeLib::getDeferredChargesForOperations($data->cOperationCharges);

		throw new ViewAction($data);
	})
	->write('doClose', function($data) {

		\account\FinancialYearLib::closeFinancialYear($data->e);

		throw new RedirectAction(\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/?success=account:FinancialYear::closed');
	});
?>
