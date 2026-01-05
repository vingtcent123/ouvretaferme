<?php
new Page(function($data) {
	$data->eFarm->validate('hasAccounting');
})
	->get('/banque/operations', function($data) {

		$data->nSuggestion = \preaccounting\SuggestionLib::countWaiting();

		$data->eImport = \bank\ImportLib::getById(GET('import', 'int'));

		$search = new Search([
			'date' => \bank\Cashflow::GET('date', 'date'),
			'fitid' => GET('fitid'),
			'memo' => GET('memo'),
			'status' => \bank\Cashflow::GET('status', 'status'),
			'from' => \bank\Cashflow::GET('periodStart', 'date'),
			'to' => \bank\Cashflow::GET('periodEnd', 'date'),
			'periodStart' => \bank\Cashflow::GET('periodStart', 'date'),
			'periodEnd' => \bank\Cashflow::GET('periodEnd', 'date'),
			'type' => \bank\Cashflow::GET('type', 'type'),
			'isReconciliated' => GET('isReconciliated', '?bool'),
			'id' => GET('id'),
			'import' => $data->eImport,
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

		$data->cBankAccount = \bank\BankAccountLib::getAll('id');

		if(get_exists('bankAccount')) {
			$eBankAccount = \bank\BankAccountLib::getById(GET('bankAccount'));
		} else if(\session\SessionLib::exists('bankAccount')) {
			$eBankAccount = \bank\BankAccountLib::getById(\session\SessionLib::get('bankAccount'));
		} else if($data->cBankAccount->notEmpty()) {
			$eBankAccount = $data->cBankAccount->first();
		} else {
			$eBankAccount = new \bank\BankAccount();
		}

		if($eBankAccount->empty() and $data->cBankAccount->notEmpty()) {
			$eBankAccount = $data->cBankAccount->first();
		}
		$search->set('bankAccount', $eBankAccount);
		if($eBankAccount->notEmpty()) {
			\session\SessionLib::set('bankAccount', $eBankAccount['id']);
		}


		$hasSort = get_exists('sort') === TRUE;
		$data->search = clone $search;

		$data->page = GET('page', 'int');

		[$data->cCashflow, $data->nCashflow, $data->nPage] = \bank\CashflowLib::getAll($search, $data->page, $hasSort);

		list($data->minDate, $data->maxDate) = \bank\CashflowLib::getMinMaxDate();
		$data->eImportCurrent = \bank\ImportLib::getLastImport();
		if($data->eImportCurrent->notEmpty()) {
			$data->eImportCurrent['nCashflowWaiting'] = \bank\CashflowLib::countSuggestionWaitingByImport($data->eImportCurrent);
		}

		throw new ViewAction($data);

	});

new \bank\CashflowPage(function($data) {

	$data->eFarm->validate('usesAccounting');

})
	->applyElement(function($data, \bank\Cashflow $e) {

		if(\account\FinancialYearLib::isDateInFinancialYear($e['date'], $data->eFarm['eFinancialYear']) === FALSE) {
			throw new NotExistsAction();
		}

		if($e->acceptAllocate() === FALSE) {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations');
		}

	})
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
			'paymentMethod' => POST('paymentMethod', 'payment\Method'),
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
;

new \bank\CashflowPage(function($data) {

	$data->eFarm->validate('hasAccounting');

})
->write('doDelete', function($data) {

	\bank\CashflowLib::deleteCasfhlow($data->e);

	throw new ReloadAction('bank', 'Cashflow::deleted');

})
->write('undoDelete', function($data) {

	\bank\CashflowLib::undeleteCashflow($data->e);

	throw new ReloadAction('bank', 'Cashflow::undeleted');

}, validate: ['acceptUndoDelete'])
->read('deAllocate', function($data) {

	$data->action = GET('action');

	$data->cOperation = \journal\OperationLib::getByHash($data->e['hash']);

	throw new ViewAction($data);

}, validate: ['acceptDeallocate'])
->write('doDeallocate', function($data) {

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

	throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations?success=bank:Cashflow::deallocated.'.$action);

}, validate: ['acceptDeallocate'])
;

?>
