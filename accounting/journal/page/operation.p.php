<?php
new \journal\OperationPage(
	function($data) {
		\user\ConnectionLib::checkLogged();

		$data->eFarm->validate('canManage', 'hasAccounting');

		if($data->eFarm->usesAccounting() === FALSE) {
			throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
		}

	})
->read('/journal/operation/{id}', function($data) {

	$data->e['cOperationHash'] = \journal\OperationLib::getByHash($data->e['hash']);

	throw new ViewAction($data);
})
->read('/journal/operation/{id}/update', function($data) {

	$data->cOperation = \journal\OperationLib::getByHash($data->e['hash']);

	// Cas particulier des comptes en 409x : ne pas afficher l'écriture en 44581 et remettre l'écriture originale en HT
	$cOperationRegulVat = $data->cOperation->find(fn($e) => (
		// L'opération courante doit être une écriture en 44581
		\account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::VAT_DEPOSIT_CLASS) and
		$e['operation']->notEmpty() and
		// L'opération parente doit être une 409 ou une 419
		\account\AccountLabelLib::isDeposit($data->cOperation[$e['operation']['id']]['accountLabel'] ?? '')
	));

	if($cOperationRegulVat->notEmpty()) {
		foreach($cOperationRegulVat as $eOperationRegulVat) {
			$data->cOperation->offsetUnset($eOperationRegulVat['id']); // Ne pas afficher
			$data->cOperation[$eOperationRegulVat['operation']['id']]['amount'] -= $eOperationRegulVat['amount']; // Remettre le montant HT

		}
	}

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

	$eCashflow = \bank\CashflowLib::getById(POST('cashflow'));

	\journal\Operation::model()->beginTransaction();

	$cOperation = \journal\OperationLib::prepareOperations($data->eFarm, $_POST, new \journal\Operation(), for: 'update', eCashflow: $eCashflow);

	$fw->validate();

	\journal\Operation::model()->commit();

	throw new ReloadAction('journal', $cOperation->count() > 1 ? 'Operation::updatedSeveral' : 'Operation::updated');

})
->read('delete', function($data) {

	$data->cOperation = \journal\OperationLib::getByHash($data->e['hash']);

	throw new ViewAction($data);

})

->doDelete(function($data) {

	throw new ReloadAction('journal', 'Operation::deleted');

})
;

new \journal\OperationPage(
	function($data) {

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

		\journal\Operation::model()->commit();

		$success = $cOperation->count() > 1 ? 'Operation::createdSeveral' : 'Operation::created';

		$hasMissingAsset = $cOperation->find(fn($e) => $e->acceptNewAsset())->notEmpty();
		if($hasMissingAsset) {
			throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/journal/livre-journal?hash='.$cOperation->first()['hash'].'&needsAsset=1&success=journal:'.$success.'CreateAsset');
		}

		throw new RedirectAction(\company\CompanyUi::urlFarm($data->eFarm).'/journal/livre-journal?hash='.$cOperation->first()['hash'].'&success=journal:'.$success);

	});

new \journal\OperationPage(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

})
	->get('createDocumentCollection', function($data) {

		$data->c = \journal\OperationLib::getByIds(GET('ids', 'array'));

		\journal\Operation::validateBatch($data->c);

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
	->writeCollection('doUpdateDocumentCollection', function($data) {

		$document = POST('document');

		$fw = new FailWatch();

		\journal\OperationLib::updateDocumentCollection($data->c, $document);

		$fw->validate();

		throw new ReloadAction('journal', 'Operations::updated');
	});

new Page()
	->post('query', function($data) {

		$search = new Search([
			'query' => POST('query'),
			'cashflow' => \bank\CashflowLib::getById(POST('cashflow')),
			'thirdParty' => \account\ThirdPartyLib::getById(POST('thirdParty')),
			'excludedOperationIds' => explode(',', POST('excludedOperations')),
			'excludedPrefix' => explode(',', POST('excludedPrefix')),
		]);
		$data->eCashflow = $search->get('cashflow');

		$data->cOperation = \journal\OperationLib::getForAttachQuery($data->eCashflow, $search);

		throw new \ViewAction($data);

	})
	->post('queryForDeferral', function($data) {

 		$data->cOperation = \journal\OperationLib::getForDeferral(POST('query'), $data->eFarm['eFinancialYear']);

		throw new \ViewAction($data);

	})
	->post('queryDescription', function($data) {

		$accountLabel = POST('accountLabel');
		$eThirdParty = \account\ThirdPartyLib::getById(POST('thirdParty'));

		if($accountLabel and $eThirdParty->notEmpty()) {
			$data->descriptions = \journal\OperationLib::getDescriptions($accountLabel, $eThirdParty);
		} else {
			$data->descriptions = [];
		}

		throw new \ViewAction($data);

	});
?>
