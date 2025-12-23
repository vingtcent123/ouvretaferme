<?php
new Page(function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

})
	->get('/banque/operations', function($data) {

		$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-invoice-cashflow');
		$data->tipNavigation = 'close';

		$data->nSuggestion = \preaccounting\SuggestionLib::countWaitingByCashflow();

		if($data->eFarm->usesAccounting()) {
			$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
			$data->cFinancialYear = \account\FinancialYearLib::getAll();
		} else {
			$data->eFinancialYear = new \account\FinancialYear();
			$data->cFinancialYear = new Collection();
		}

		if(GET('periodStart')) {
			$periodStart = GET('periodStart').'-01';
		} else {
			$periodStart = '';
		}

		if(GET('periodEnd') and \util\DateLib::isValid(GET('periodEnd').'-01')) {
			$periodEnd = date('Y-m-d', strtotime(GET('periodEnd').'-01 + 1 month - 1 day'));
		} else {
			$periodEnd = '';
		}
		$search = new Search([
			'date' => GET('date'),
			'fitid' => GET('fitid'),
			'memo' => GET('memo'),
			'status' => \bank\Cashflow::GET('status', 'status'),
			'amount' => GET('amount'),
			'margin' => GET('margin'),
			'from' => $periodStart,
			'to' => $periodEnd,
			'periodStart' => GET('periodStart'),
			'periodEnd' => GET('periodEnd'),
			'isReconciliated' => GET('isReconciliated', '?bool'),
			'bankAccount' => \bank\BankAccountLib::getById(GET('bankAccount')),
			'id' => GET('id'),
			'financialYear' => $data->eFarm->usesAccounting() ? \account\FinancialYearLib::getById(GET('year')) : new \account\FinancialYear(),
		], GET('sort', default: 'date-'));

		if(GET('amount')) {

			$amount = GET('amount','float');
			$margin = GET('margin', 'float', 0);

			$search->set('amountMin', $amount - $margin);

			if($margin) {
				$search->set('amountMax', $amount + $margin);
			} else {
				$search->set('amountMax', (int)$amount + 1);
			}
		}

		if(GET('direction') and in_array(GET('direction'), ['debit', 'credit'])) {
			$search->set('direction', GET('direction'));
		}

		if(GET('status-shortcut')) {
			$search->set('status', \bank\Cashflow::WAITING);
		}

		$hasSort = get_exists('sort') === TRUE;
		$data->search = clone $search;

		if(get_exists('import') === TRUE) {
			$search->set('import', GET('import'));
			$data->eImport = \bank\ImportLib::getById(GET('import', 'int'));
		} else {
			$data->eImport = new \bank\Import();
		}

		$data->page = GET('page', 'int');

		list($data->minDate, $data->maxDate) = \bank\CashflowLib::getMinMaxDate();
		$data->nCashflow = \bank\CashflowLib::countByStatus($search);
		$data->nCashflow->offsetSet('all', new \bank\Cashflow(['count' => array_sum($data->nCashflow->getColumn('count'))]));
		[$data->cCashflow, $data->nCashflowSearch, $data->nPage] = \bank\CashflowLib::getAll($search, $data->page, $hasSort);

		$data->cBankAccount = \bank\BankAccountLib::getAll();

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
			'paymentDate' => $data->eCashflow['date'],
		]), eCashflow: $data->eCashflow);

		if($cOperation->empty() === TRUE) {
			\Fail::log('Cashflow::allocate.noOperation');
		}

		$fw->validate();

		\bank\Cashflow::model()->update(
			$data->eCashflow,
			['status' => \bank\CashflowElement::ALLOCATED, 'updatedAt' => \bank\Cashflow::model()->now(), 'hash' => $cOperation->first()['hash']]
		);

		\journal\Operation::model()->commit();

		throw new ReloadAction('bank', 'Cashflow::allocated');

	})
	->get('attach', function($data) {

		$data->eThirdParty = \account\ThirdPartyLib::getById(GET('thirdParty'));
		$data->cOperation = \journal\OperationLib::getByIds(GET('operations', 'array'));
		\journal\OperationLib::setHashOperations($data->cOperation);

		throw new ViewAction($data);

	})
	->get('calculateAttach', function($data) {

		$data->eThirdParty = \account\ThirdPartyLib::getById(GET('thirdParty'));

		$data->cOperationSelected = \journal\OperationLib::getByIds(GET('operations', 'array'));
		\journal\OperationLib::setHashOperations($data->cOperationSelected);

		$hashes = array_unique($data->cOperationSelected->getColumn('hash'));
		$cOperationAll = new Collection();
		foreach($hashes as $hash) {
			$cOperationAll->mergeCollection(\journal\OperationLib::getByHash($hash));
		}
		$data->cOperation = $cOperationAll;

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

		if(post_exists('operations') === FALSE) {
			\bank\Cashflow::fail('noSelectedOperation');
		}

		$eThirdParty = \account\ThirdPartyLib::getById(POST('thirdParty'));
		$cOperation = \journal\OperationLib::getOperationsForAttach(POST('operations', 'array'));
		\journal\Operation::validateBatchAttach($data->eCashflow, $cOperation);

		\bank\CashflowLib::attach($data->eCashflow, $cOperation, $eThirdParty);

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
