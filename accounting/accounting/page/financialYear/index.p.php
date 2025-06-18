<?php
new Page()
	->get('index', function($data) {

		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
		$data->cFinancialYear = \accounting\FinancialYearLib::getAll();
		$data->cFinancialYearOpen = \accounting\FinancialYearLib::getOpenFinancialYears();

		throw new ViewAction($data);

	});

new \accounting\FinancialYearPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
	}
)
	->create(function($data) {

		$data->cFinancialYearOpen = \accounting\FinancialYearLib::getOpenFinancialYears();
		if($data->cFinancialYearOpen->count() >= 2) {
			throw new NotExpectedAction('Cannot create a new financial year as there are already '.$data->cFinancialYearOpen->count().' financial years open');
		}

		$nextDates = \accounting\FinancialYearLib::getNextFinancialYearDates();
		$data->e['startDate'] = $nextDates['startDate'];
		$data->e['endDate'] = $nextDates['endDate'];

		throw new ViewAction($data);

	})
	->doCreate(function($data) {

		$data->cFinancialYearOpen = \accounting\FinancialYearLib::getOpenFinancialYears();
		if($data->cFinancialYearOpen->count() >= 2) {
			throw new NotExpectedAction('Cannot create a new financial year as there are already '.$data->cFinancialYearOpen->count().' financial years open');
		}

		throw new ReloadAction('accounting', 'FinancialYear::created');

	})
	->update(function($data) {

		throw new ViewAction($data);

	})
	->doUpdate(function($data) {

		throw new ReloadAction('accounting', 'FinancialYear::updated');

	})
	->write('close', function($data) {

		\accounting\FinancialYearLib::closeFinancialYear($data->e, createNew: FALSE);

		throw new RedirectAction(\company\CompanyUi::urlAccounting($data->eCompany).'/financialYear/?success=accounting:FinancialYear::closed');
	})
	->write('closeAndCreateNew', function($data) {

		\accounting\FinancialYearLib::closeFinancialYear($data->e, createNew: TRUE);

		throw new RedirectAction(\company\CompanyUi::urlAccounting($data->eCompany).'/financialYear/?success=accounting:FinancialYear::closedAndCreated');
	});
?>
