<?php
new \journal\OperationPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage', 'hasAccounting');

		if($data->eFarm->usesAccounting() === FALSE) {
			throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
		}

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));
		$data->cFinancialYear = \account\FinancialYearLib::getAll();

	})
->read('/journal/operation/{id}', function($data) {

	if($data->e['financialYear']->isAccrualAccounting() or $data->e['financialYear']->isCashAccrualAccounting()) {
		$data->e['cLetteringCredit'] = \journal\LetteringLib::getByOperation('credit', $data->e);
		$data->e['cLetteringDebit'] = \journal\LetteringLib::getByOperation('debit', $data->e);
	}
	$data->e['cOperationHash'] = \journal\OperationLib::getByHash($data->e['hash']);

	throw new ViewAction($data);
})
->read('/journal/operation/{id}/update', function($data) {

	$data->cOperation = \journal\OperationLib::getByHash($data->e['hash']);
	$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);

	$cOperationCashflow = new Collection();
	foreach($data->cOperation as $eOperation) {
		$cOperationCashflow->mergeCollection($eOperation['cOperationCashflow']);
	}

	if($cOperationCashflow->notEmpty()) {
		$data->eCashflow = $cOperationCashflow->first()['cashflow'];
	} else {
		$data->eCashflow = new \bank\Cashflow();
	}

	$data->e['cJournalCode'] = \journal\JournalCodeLib::getAll();

	throw new ViewAction($data);
})
->post('/journal/operation/{id}/doUpdate', function($data) {

	$fw = new FailWatch();

	\journal\Operation::model()->beginTransaction();

	$cOperation = \journal\OperationLib::prepareOperations($data->eFarm, $_POST, new \journal\Operation(), for: 'update');

	$fw->validate();

	\journal\Operation::model()->commit();

	throw new ReloadAction('journal', $cOperation->count() > 1 ? 'Operation::updatedSeveral' : 'Operation::updated');

});

new \journal\OperationPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

		if($data->eFarm->usesAccounting() === FALSE) {
			throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
		}

		// Payment methods
		$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, NULL, NULL);
	}
)
	->applyElement(function($data, \journal\Operation $e) {
		$e['cPaymentMethod'] = $data->cPaymentMethod;
		$e['farm'] = $data->eFarm;
	})
	->quick(['document', 'description', 'amount', 'comment', 'paymentMethod', 'journalCode'], validate: ['canUpdateQuick'])
	->create(function($data) {

		if(get_exists('account') === TRUE) {
			$eAccount = \account\AccountLib::getByIdWithVatAccount(GET('account', 'int'));
		} elseif(get_exists('accountPrefix') === TRUE) {
			$eAccount = \account\AccountLib::getByPrefixWithVatAccount(GET('accountPrefix', 'int'));
		} else {
			$eAccount = new \account\Account();
		}

		if(get_exists('cashflow') === TRUE) {
			$eCashflow = \bank\CashflowLib::getById(GET('cashflow', 'int'));
		} else {
			$eCashflow = new \bank\Cashflow();
		}
		// Apply default bank account label if the class is a bank account class.
		$label = '';
		if(get_exists('accountLabel') and mb_strlen(GET('accountLabel') > 0)) {
			$label = GET('accountLabel');
		} elseif($eAccount->exists() === TRUE and $eAccount['class'] === \account\AccountSetting::BANK_ACCOUNT_CLASS) {
			$eAccountBank = \bank\BankAccountLib::getDefaultAccount();
			if($eAccountBank->exists() === TRUE) {
				$label = $eAccountBank['accountLabel'];
			}
		}

		// Third party
		$thirdParty = account\ThirdPartyLib::getById(GET('thirdParty', 'int'));
		$cJournalCode = \journal\JournalCodeLib::getAll();
		$eJournalCode = (get_exists('code') and $cJournalCode->offsetExists(GET('code', 'int'))) ? $cJournalCode->offsetGet(GET('code', 'int')) : new \journal\JournalCode();

		$data->e->merge([
			'farm' => $data->eFarm['id'],
			'account' => $eAccount,
			'accountLabel' => $label,
			'vatRate' => $eAccount['vatRate'] ?? 0,
			'thirdParty' => $thirdParty,
			'date' => GET('date'),
			'description' => GET('description'),
			'document' => GET('document'),
			'type' => GET('type'),
			'amount' => GET('amount', 'float'),
			'cashflow' => $eCashflow,
			'cJournalCode' => $cJournalCode,
			'journalCode' => $eJournalCode,
		]);

		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

		throw new ViewAction($data);

	})
	->post('readInvoice', function($data) {

		if(POST('columns', 'int') !== 1) {
			throw new NotExpectedAction('Impossible to read an invoice when there are more than 1 operation in form.');
		}

		$fw = new FailWatch();

		$data->operation = \journal\OperationLib::readInvoice($data->eFarm, $_FILES['invoice']);
		$data->eFinancialYear = \account\FinancialYearLib::selectDefaultFinancialYear();

		$fw->validate();

		$data->ePartner = \account\DropboxLib::getPartner();

		throw new ViewAction($data);

	})
	->post('selectAccount', function($data) {

		$data->index = POST('index');
		$vatRate = POST('vatRate', 'float');
		$data->eAccount = \account\AccountLib::getById(POST('account'));

		if($vatRate !== NULL) {
			$data->eAccount['vatRate'] = $vatRate;
		}

		throw new ViewAction($data);

	})
	->post('selectThirdParty', function($data) {

		$data->index = POST('index');
		$data->eThirdParty = \account\ThirdPartyLib::getById(POST('thirdParty'));

		throw new ViewAction($data);

	})
	->post('addOperation', function($data) {

		$data->index = POST('index');
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

		$eThirdParty = post_exists('thirdParty') ? \account\ThirdPartyLib::getById(POST('thirdParty')) : new \account\ThirdParty();
		$cJournalCode = \journal\JournalCodeLib::getAll();
		$eJournalCode = (post_exists('code') and $cJournalCode->offsetExists(POST('code', 'int'))) ? $cJournalCode->offsetGet(POST('code', 'int')) : new \journal\JournalCode();
		$data->eOperation = new \journal\Operation([
			'account' => new \account\Account(),
			'thirdParty' => $eThirdParty,
			'cJournalCode' => $cJournalCode,
			'journalCode' => $eJournalCode
		]);

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$fw = new FailWatch();

		\journal\Operation::model()->beginTransaction();

		$accounts = post('account', 'array', []);

		if(count($accounts) === 0) {
			\Fail::log('Operation::allocate.accountsCheck');
		}

		$cOperation = \journal\OperationLib::prepareOperations($data->eFarm, $_POST, new \journal\Operation());

		if($cOperation->empty() === TRUE) {
			\Fail::log('Operation::allocate.noOperation');
		}

		$fw->validate();

		\journal\OperationLib::saveInvoiceToDropbox(POST('invoiceFile'), $cOperation);

		\journal\Operation::model()->commit();

		throw new ReloadAction('journal', $cOperation->count() > 1 ? 'Operation::createdSeveral' : 'Operation::created');

	})
	->create(function($data) {

		// Third party
		$thirdParty = account\ThirdPartyLib::getById(GET('thirdParty', 'int'));
		$data->eFinancialYear = \account\FinancialYearLib::getDynamicFinancialYear($data->eFarm, GET('financialYear', 'int'));

		$data->e->merge([
			'farm' => $data->eFarm,
			'thirdParty' => $thirdParty,
			'date' => GET('date'),
			'description' => GET('description'),
			'document' => GET('document'),
			'type' => GET('type'),
			'amount' => GET('amount', 'float'),
			'cPaymentMethod' => $data->cPaymentMethod,
			'cJournalCode' => \journal\JournalCodeLib::getAll(),
			'financialYear' => $data->eFinancialYear,
		]);


		$data->cBankAccount = \bank\BankAccountLib::getAll();

		throw new ViewAction($data);
	}, page: 'createPayment')
	->post('doCreatePayment', function($data) {

		$fw = new FailWatch();

		\journal\Operation::model()->beginTransaction();

		$cOperation = \journal\OperationLib::preparePayments($_POST);

		if($cOperation === NULL or $cOperation->empty() === TRUE) {
			\Fail::log('Operation::payment.noOperation');
		}

		$fw->validate();

		\journal\Operation::model()->commit();

		throw new ReloadAction('journal', 'Operation::payment.created');

	});

new Page(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage');

		if($data->eFarm->usesAccounting() === FALSE) {
			throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
		}

	})
	->post('getWaiting', function($data) {

		$data->cOperation = \journal\OperationLib::getWaiting(POST('thirdParty', 'account\ThirdParty'));

		throw new ViewAction($data);

	});

new \journal\OperationPage(
	function($data) {
		\user\ConnectionLib::checkLogged();
		$data->eFarm->validate('canManage');

		if($data->eFarm->usesAccounting() === FALSE) {
			throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
		}

		$data->eOperation = \journal\OperationLib::getById(REQUEST('id', 'int'))->validate('canDelete');
	}
)
->post('doDelete', function($data) {

	$fw = new FailWatch();

	\journal\OperationLib::delete($data->eOperation);

	if($fw->ok()) {

		throw new ReloadAction('journal', 'Operation::deleted');

	}
});

new \journal\OperationPage(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->get('createCommentCollection', function($data) {

		throw new ViewAction($data);

	})
	->get('createDocumentCollection', function($data) {

		throw new ViewAction($data);

	})
	->writeCollection('doUpdatePaymentCollection', function($data) {

		$ePaymentMethod = \payment\MethodLib::getById(POST('paymentMethod'))->validate('canUse');

		$fw = new FailWatch();

		\journal\OperationLib::updatePaymentMethodCollection($data->c, $ePaymentMethod);

		$fw->validate();

		throw new ReloadAction('journal', 'Operations::updated');

	})
	->writeCollection('doUpdateJournalCollection', function($data) {

		$eJournalCode = POST('journalCode', 'journal\journalCode');

		$fw = new FailWatch();

		\journal\OperationLib::updateJournalCodeCollection($data->c, $eJournalCode);

		$fw->validate();

		throw new ReloadAction('journal', 'Operations::updated');
	})
	->writeCollection('doUpdateCommentCollection', function($data) {

		$comment = POST('comment');

		$fw = new FailWatch();

		\journal\OperationLib::updateCommentCollection($data->c, $comment);

		$fw->validate();

		throw new ReloadAction('journal', 'Operations::updated');
	})
	->writeCollection('doUpdateDocumentCollection', function($data) {

		$comment = POST('document');

		$fw = new FailWatch();

		\journal\OperationLib::updateDocumentCollection($data->c, $comment);

		$fw->validate();

		throw new ReloadAction('journal', 'Operations::updated');
	});
?>
