<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canView');

		\Setting::set('main\viewBank', 'cashflow');
	}
)
	->get('index', function($data) {

		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));

		$search = new Search([
			'date' => GET('date'),
			'fitid' => GET('fitid'),
			'memo' => GET('memo'),
			'status' => GET('status'),
		], GET('sort'));
		$hasSort = get_exists('sort') === TRUE;
		$data->search = clone $search;

		// Ne pas ouvrir le bloc de recherche pour ces champs
		$search->set('financialYear', $data->eFinancialYear);
		if(get_exists('import') === TRUE) {
			$search->set('import', GET('import'));
			$data->eImport = \bank\ImportLib::getById(GET('import', 'int'));
		} else {
			$data->eImport = new \bank\Import();
		}

		$data->nCashflow = \bank\CashflowLib::countByStatus($search);
		$data->cCashflow = \bank\CashflowLib::getAll($search, $hasSort);

		throw new ViewAction($data);

	});

new \bank\CashflowPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
		$data->eCashflow = \bank\CashflowLib::getById(INPUT('id'))->validate('canAllocate');

		\Setting::set('main\viewBank', 'import');
		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
	}
)
	->get('allocate', function($data) {

		throw new ViewAction($data);

	})
	->post('addAllocate', function($data) {

		$data->index = POST('index');
		$eThirdParty = post_exists('thirdParty') ? \journal\ThirdPartyLib::getById(POST('thirdParty')) : new \journal\ThirdParty();
		$data->eOperation = new \journal\Operation(['account' => new \accounting\Account(), 'thirdParty' => $eThirdParty]);

		throw new ViewAction($data);

	})
	->post('doAllocate', function($data) {
		$data->eCashflow = \bank\CashflowLib::getById(INPUT('id'),
			\bank\Cashflow::getSelection() +
			[
				'import' => \bank\Import::getSelection() +
					['account' => \bank\Account::getSelection()]
			]
		);
		$fw = new FailWatch();

		\journal\Operation::model()->beginTransaction();

		$accounts = post('account', 'array', []);

		if(count($accounts) === 0) {
			Fail::log('Cashflow::allocate.accountsCheck');
		}

		$cOperation = \journal\OperationLib::prepareOperations($_POST, new \journal\Operation([
			'cashflow' => $data->eCashflow,
			'date' => $data->eCashflow['date'],
		]));

		if($cOperation->empty() === TRUE) {
			\Fail::log('Cashflow::allocate.noOperation');
		}

		$fw->validate();

		\bank\Cashflow::model()->update(
			$data->eCashflow,
			['status' => \bank\CashflowElement::ALLOCATED, 'updatedAt' => \bank\Cashflow::model()->now()]
		);

		\journal\Operation::model()->commit();

		throw new ReloadAction('bank', 'Cashflow::allocated');

	})
	->get('attach', function($data) {

		$data->cOperation = \journal\OperationLib::getOperationsForAttach($data->eCashflow);

		throw new ViewAction($data);

	})
	->post('doAttach', function($data) {

		$data->eCashflow = \bank\CashflowLib::getById(INPUT('id'),
			\bank\Cashflow::getSelection() +
			[
				'import' => \bank\Import::getSelection() +
					['account' => \bank\Account::getSelection()]
			]
		);

		$fw = new FailWatch();

		if($data->eCashflow->exists() === FALSE) {
			\bank\Cashflow::fail('internal');
		}

		if(post_exists('operation') === FALSE) {
			\bank\Cashflow::fail('noSelectedOperation');
		}

		\bank\CashflowLib::attach($data->eCashflow, POST('operation', 'array'));

		$fw->validate();

		throw new ReloadAction('bank', 'Cashflow::attached');

	});

new \bank\CashflowPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$company = GET('company');

		$data->eCompany = \company\CompanyLib::getById($company)->validate('canWrite');
		$data->eCashflow = \bank\CashflowLib::getById(INPUT('id'));

		\Setting::set('main\viewBank', 'import');
		[$data->cFinancialYear, $data->eFinancialYear] = \company\EmployeeLib::getDynamicFinancialYear($data->eCompany, GET('financialYear', 'int'));
	}
)
->post('deAllocate', function($data) {

	$fw = new FailWatch();

	if($data->eCashflow->exists() === FALSE) {
		\bank\Cashflow::fail('internal');
	}

	$fw->validate();

	\journal\OperationLib::deleteByCashflow($data->eCashflow);

	$data->eCashflow['status'] = \bank\CashflowElement::WAITING;
	\bank\CashflowLib::update($data->eCashflow, ['status']);

	throw new ReloadAction('bank', 'Cashflow::deallocated');

})
?>
