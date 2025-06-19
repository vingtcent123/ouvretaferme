<?php
new Page(function($data) {

	$data->eCompany = \company\CompanyLib::getById(GET('company'))->validate('canView')->validate('isAccrualAccounting');

	[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

})
	->get('index', function($data) {

		\user\ConnectionLib::checkLogged();
		\Setting::set('main\viewJournal', 'account');

		$data->eThirdParty = get_exists('thirdParty')
			? \journal\ThirdPartyLib::getById(GET('thirdParty', 'int'))
			: NULL;

		$accountType = GET('accountType',  'string', 'customer');
		$accountLabel = match($accountType) {
			'supplier' => '401',
			default => '411',
		};

		$search = new Search([
			'date' => GET('date'),
			'description' => GET('description'),
			'type' => GET('type'),
			'document' => GET('document'),
			'thirdParty' => GET('thirdParty'),
			'asset' => GET('asset'),
		], GET('sort'));

		$search->set('letteringFilter', GET('letteringFilter', 'bool'));

		$hasSort = get_exists('sort') === TRUE;
		$data->search = clone $search;
		// Ne pas ouvrir le bloc de recherche
		$search->set('financialYear', $data->eFinancialYear);
		$search->set('accountLabel', $accountLabel);

		$data->eCashflow = \bank\CashflowLib::getById(GET('cashflow'));
		if($data->eCashflow->exists() === TRUE) {
			$search->set('cashflow', GET('cashflow'));
		}

		$data->cOperation = \journal\OperationLib::getAllForAccounting($search, $hasSort);
		$data->cAccount = \account\AccountLib::getAll();

		throw new ViewAction($data);

	});
?>
