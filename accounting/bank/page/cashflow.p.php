<?php
new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

	}
)
	->get('/banque/operations', function($data) {

		$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-invoice-cashflow');
		$data->tipNavigation = 'close';

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

		$search = new Search([
			'date' => GET('date'),
			'fitid' => GET('fitid'),
			'memo' => GET('memo'),
			'status' => GET('status'),
			'amount' => GET('amount'),
			'margin' => GET('margin'),
		], GET('sort'));

		if(GET('amount')) {

			$search->set('amountMin', GET('amount','float') - GET('margin', 'float', 0));

			if(GET('margin', 'float', 0)) {
				$search->set('amountMax', GET('amount', 'float') + GET('margin', 'float', 0));
			} else {
				$search->set('amountMax', GET('amount', 'int') + 1);
			}
		}
		$search->set('statusWithDeleted', GET('statusWithDeleted', 'bool', FALSE));
		$hasSort = get_exists('sort') === TRUE;
		$data->search = clone $search;

		// Ne pas ouvrir le bloc de recherche pour ces champs
		$search->set('financialYear', $data->eFinancialYear);

		if($search->get('statusWithDeleted') === FALSE) {
			$search->set('statusNotDeleted', TRUE);
		}
		if(get_exists('import') === TRUE) {
			$search->set('import', GET('import'));
			$data->eImport = \bank\ImportLib::getById(GET('import', 'int'));
		} else {
			$data->eImport = new \bank\Import();
		}

		$data->nCashflow = \bank\CashflowLib::countByStatus($search);
		$data->nCashflow->offsetSet('all', new \bank\Cashflow(['count' => array_sum($data->nCashflow->getColumn('count'))]));
		$data->cCashflow = \bank\CashflowLib::getAll($search, $hasSort);

		throw new ViewAction($data);

	});

new \bank\CashflowPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eCashflow = \bank\CashflowLib::getById(INPUT('id'))->validate('canAllocate');

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();
	}
)
	->get('allocate', function($data) {

		// Payment methods
		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);
		$data->cJournalCode = \journal\JournalCodeLib::getAll();

		throw new ViewAction($data);

	})
	->post('addAllocate', function($data) {

		$data->index = POST('index');
		$eThirdParty = post_exists('thirdParty') ? \account\ThirdPartyLib::getById(POST('thirdParty')) : new \account\ThirdParty();
		$data->eOperation = new \journal\Operation(['account' => new \account\Account(), 'thirdParty' => $eThirdParty, 'cOperationCashflow' => new Collection(['cashflow' => $data->eCashflow]), 'cJournalCode' => \journal\JournalCodeLib::getAll()]);

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);

		throw new ViewAction($data);

	})
	->post('doAllocate', function($data) {
		$data->eCashflow = \bank\CashflowLib::getById(INPUT('id'),
			\bank\Cashflow::getSelection() +
			[
				'import' => \bank\Import::getSelection() +
					['account' => \bank\BankAccount::getSelection()]
			]
		);
		$fw = new FailWatch();

		\journal\Operation::model()->beginTransaction();

		$accounts = post('account', 'array', []);

		if(count($accounts) === 0) {
			Fail::log('Cashflow::allocate.accountsCheck');
		}

		$cOperation = \journal\OperationLib::prepareOperations($data->eFarm, $_POST, new \journal\Operation([
			'date' => $data->eCashflow['date'],
		]), eCashflow: $data->eCashflow);

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
					['account' => \bank\BankAccount::getSelection()]
			]
		);

		$fw = new FailWatch();

		if($data->eCashflow->exists() === FALSE) {
			\bank\Cashflow::fail('internal');
		}

		if(post_exists('operation') === FALSE) {
			\bank\Cashflow::fail('noSelectedOperation');
		}

		// Payment methods
		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);
		$eThirdParty = \account\ThirdPartyLib::getById(POST('thirdParty'));

		\bank\CashflowLib::attach($data->eCashflow, POST('operation', 'array'), $eThirdParty, $data->cPaymentMethod);

		$fw->validate();

		throw new ReloadAction('bank', 'Cashflow::attached');

	});

new \bank\CashflowPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');
		$data->eCashflow = \bank\CashflowLib::getById(INPUT('id'));

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

	}
)
->post('deAllocate', function($data) {

	$fw = new FailWatch();

	if($data->eCashflow->exists() === FALSE) {
		\bank\Cashflow::fail('internal');
	}

	$action = POST('action');
	if(in_array($action, ['dissociate', 'delete']) === FALSE) {
		throw new NotExpectedAction('Unable to do nothing but dissociate nor delete');
	}

	$fw->validate();

	\journal\OperationLib::unlinkCashflow($data->eCashflow, $action);

	$data->eCashflow['status'] = \bank\CashflowElement::WAITING;
	\bank\CashflowLib::update($data->eCashflow, ['status']);

	throw new ReloadAction('bank', 'Cashflow::deallocated');

})
->post('doDelete', function($data) {

	if($data->eCashflow->exists() === FALSE or $data->eCashflow->canDelete() === FALSE) {
		\bank\Cashflow::fail('internal');
	}

	\bank\CashflowLib::deleteCasfhlow($data->eCashflow);

	throw new ReloadAction('bank', 'Cashflow::deleted');

})
->post('undoDelete', function($data) {

	if($data->eCashflow->exists() === FALSE or $data->eCashflow['status'] !== \bank\Cashflow::DELETED) {
		\bank\Cashflow::fail('internal');
	}

	\bank\CashflowLib::undeleteCashflow($data->eCashflow);

	throw new ReloadAction('bank', 'Cashflow::undeleted');

});
?>
