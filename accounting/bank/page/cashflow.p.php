<?php
new Page()
	->get('/banque/operations', function($data) {

		$data->nSuggestion = \preaccounting\SuggestionLib::countWaitingByCashflow();

		$search = new Search([
			'date' => GET('date'),
			'fitid' => GET('fitid'),
			'memo' => GET('memo'),
			'status' => GET('status'),
			'from' => GET('periodStart'),
			'to' => GET('periodEnd'),
			'direction' => GET('direction'),
			'periodStart' => GET('periodStart'),
			'periodEnd' => GET('periodEnd'),
			'isReconciliated' => GET('isReconciliated', '?bool'),
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

		$data->cBankAccount = \bank\BankAccountLib::getAll();

		if(get_exists('bankAccount') === FALSE and \session\SessionLib::exists('bankAccount')) {
			$search->set('bankAccount', \bank\BankAccountLib::getById(\session\SessionLib::get('bankAccount')));
		} else {
			$search->set('bankAccount', \bank\BankAccountLib::getById(GET('bankAccount')));
		}
		$eBankAccountSelected = $search->get('bankAccount');
		if($eBankAccountSelected->empty() or $data->cBankAccount->offsetExists($eBankAccountSelected['id']) === FALSE) {
			$eBankAccountSelected = $data->cBankAccount->first();
		} else {
			$eBankAccountSelected = $data->cBankAccount->offsetGet($eBankAccountSelected['id']);
		}
		$search->set('bankAccount', $eBankAccountSelected);
		\session\SessionLib::set('bankAccount', $eBankAccountSelected['id']);

		$hasSort = get_exists('sort') === TRUE;
		$data->search = clone $search;

		if(get_exists('import') === TRUE) {
			$search->set('import', GET('import'));
			$data->eImport = \bank\ImportLib::getById(GET('import', 'int'));
		} else {
			$data->eImport = new \bank\Import();
		}

		$data->page = GET('page', 'int');

		[$data->cCashflow, $data->nCashflow, $data->nPage] = \bank\CashflowLib::getAll($search, $data->page, $hasSort);

		list($data->minDate, $data->maxDate) = \bank\CashflowLib::getMinMaxDate();
		$data->eImportCurrent = \bank\ImportLib::getLastImport();
		if($data->eImportCurrent->notEmpty()) {
			$data->eImportCurrent['nCashflowWaiting'] = \bank\CashflowLib::countSuggestionWaitingByImport($data->eImportCurrent);
		}

		throw new ViewAction($data);

	});

new \bank\CashflowPage()
	->read('allocate', function($data) {

		// Payment methods
		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);
		$data->cJournalCode = \journal\JournalCodeLib::getAll();

		throw new ViewAction($data);

	})
	->write('addAllocate', function($data) {

		$data->index = POST('index');
		$eThirdParty = post_exists('thirdParty') ? \account\ThirdPartyLib::getById(POST('thirdParty')) : new \account\ThirdParty();
		$data->eOperation = new \journal\Operation(['account' => new \account\Account(), 'thirdParty' => $eThirdParty, 'cOperationCashflow' => new Collection(['cashflow' => $data->e]), 'cJournalCode' => \journal\JournalCodeLib::getAll()]);

		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);

		throw new ViewAction($data);

	})
	->write('doAllocate', function($data) {

		\bank\CashflowLib::getImportData($data->e);

		$fw = new FailWatch();

		\journal\Operation::model()->beginTransaction();

		$accounts = post('account', 'array', []);

		if(count($accounts) === 0) {
			Fail::log('Cashflow::allocate.accountsCheck');
		}

		$cOperation = \journal\OperationLib::prepareOperations($data->eFarm, $_POST, new \journal\Operation([
			'paymentDate' => $data->e['date'],
		]), eCashflow: $data->e);

		if($cOperation->empty() === TRUE) {
			\Fail::log('Cashflow::allocate.noOperation');
		}

		$fw->validate();

		\bank\Cashflow::model()->update(
			$data->e,
			['status' => \bank\CashflowElement::ALLOCATED, 'updatedAt' => \bank\Cashflow::model()->now(), 'hash' => $cOperation->first()['hash']]
		);

		\journal\Operation::model()->commit();

		throw new ReloadAction('bank', 'Cashflow::allocated');

	})
	->read('attach', function($data) {

		$data->eThirdParty = \account\ThirdPartyLib::getById(GET('thirdParty'));

		$data->tip = \farm\TipLib::pickOne($data->eUserOnline, 'accounting-cashflow-attach');

		throw new ViewAction($data);

	})
	->read('calculateAttach', function($data) {

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
	->write('doAttach', function($data) {

		\bank\CashflowLib::getImportData($data->e);

		$fw = new FailWatch();

		if(post_exists('operations') === FALSE) {
			\bank\Cashflow::fail('operationsRequiredForAttach', wrapper: 'operation');
		}

		$eThirdParty = \account\ThirdPartyLib::getById(POST('thirdParty'));
		if($eThirdParty->empty()) {
			\bank\Cashflow::fail('thirdPartyRequiredForAttach', wrapper: 'third-party');
		}

		$cOperation = \journal\OperationLib::getOperationsForAttach(POST('operations', 'array'));

		$fw->validate();

		\bank\CashflowLib::attach($data->e, $cOperation, $eThirdParty);

		throw new ReloadAction('bank', 'Cashflow::attached');

	})
->write('deAllocate', function($data) {

	$fw = new FailWatch();

	if($data->e->exists() === FALSE) {
		\bank\Cashflow::fail('internal');
	}

	$action = POST('action');
	if(in_array($action, ['dissociate', 'delete']) === FALSE) {
		throw new NotExpectedAction('Unable to do nothing but dissociate nor delete');
	}

	$fw->validate();

	\journal\OperationLib::unlinkCashflow($data->e, $action);

	throw new ReloadAction('bank', 'Cashflow::deallocated.'.$action);

}, validate: ['acceptDeallocate'])
->write('doDelete', function($data) {

	\bank\CashflowLib::deleteCasfhlow($data->e);

	throw new ReloadAction('bank', 'Cashflow::deleted');

})
->write('undoDelete', function($data) {

	\bank\CashflowLib::undeleteCashflow($data->e);

	throw new ReloadAction('bank', 'Cashflow::undeleted');

}, validate: ['acceptUndoDelete'])
;

?>
