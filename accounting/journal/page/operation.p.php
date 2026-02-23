<?php
new \journal\OperationPage(function($data) {
	\user\ConnectionLib::checkLogged();

	$data->eFarm->validate('canManage', 'hasAccounting');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

})
->read('/journal/operation/{id}', function($data) {

	$data->e['cOperationHash'] = \journal\OperationLib::getByHash($data->e['hash']);
	$data->e['payment'] = \preaccounting\PaymentLib::getByHash($data->e['hash']);

	throw new ViewAction($data);
})
->read('/journal/operation/{id}/update', function($data) {

	$data->cJournalCode = \journal\JournalCodeLib::deferred();

	$data->referer = SERVER('HTTP_REFERER');

	$data->cOperation = \journal\OperationLib::getByHash($data->e['hash']);
	foreach($data->cOperation as $eOperation) {
		$eOperation->validate('isNotLinkedToAsset');
	}

	$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, FALSE, NULL);

	$cOperationCashflow = new Collection();
	foreach($data->cOperation as $eOperation) {
		$cOperationCashflow->mergeCollection($eOperation['cOperationCashflow']);
	}

	if($cOperationCashflow->notEmpty()) {
		$data->eCashflow = $cOperationCashflow->first()['cashflow'];
	} else {
		$data->eCashflow = new \bank\Cashflow();
	}

	$data->hasVatAccounting = \farm\ConfigurationLib::getConfigurationForDate($data->eFarm, 'hasVatAccounting', $data->cOperation->first()['date']);

	throw new ViewAction($data);
})
->post('/journal/operation/{id}/doUpdate', function($data) {

	$fw = new FailWatch();

	$eCashflow = \bank\CashflowLib::getById(POST('cashflow'));

	\journal\Operation::model()->beginTransaction();

	$cOperation = \journal\OperationLib::prepareOperations($data->eFarm, $_POST, 'update', $eCashflow);

	if($fw->ko()) {
		\journal\Operation::model()->rollBack();
	} else {
		\journal\Operation::model()->commit();
	}

	$fw->validate();

	$success = $cOperation->count() > 1 ? 'Operation::updatedSeveral' : 'Operation::updated';

	if(post_exists('referer')) {

		$data->url = POST('referer').'&success=journal\\'.$success;

	} else {

		$hasMissingAsset = $cOperation->find(fn($e) => $e->acceptNewAsset())->notEmpty();

		if($hasMissingAsset) {
			$data->url = \farm\FarmUi::urlConnected($data->eFarm).'/journal/livre-journal?hash='.$cOperation->first()['hash'].'&needsAsset=1&success=journal\\'.$success.'CreateAsset';
		}

		$data->url = \farm\FarmUi::urlConnected($data->eFarm).'/journal/livre-journal?hash='.$cOperation->first()['hash'].'&success=journal\\'.$success;

	}

	$data->cOperation = $cOperation;

	throw new ViewAction($data);

})
->read('delete', function($data) {

	$data->cOperation = \journal\OperationLib::getByHash($data->e['hash']);

	throw new ViewAction($data);

})

->doDelete(function($data) {

	throw new ReloadAction('journal', 'Operation::deleted');

})
;

new \journal\OperationPage(function($data) {

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

	// Payment methods
	$data->cPaymentMethod = \payment\MethodLib::getByFarm($data->eFarm, NULL, FALSE, NULL);
})
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
		$cJournalCode = \journal\JournalCodeLib::deferred();
		$eJournalCode = \journal\JournalCodeLib::ask(GET('journalCode', 'int'));

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
			'journalCode' => $eJournalCode,
		]);

		$data->cJournalCode = $cJournalCode;
		$data->hasVatAccounting = \farm\ConfigurationLib::getConfigurationForDate($data->eFarm, 'hasVatAccounting', $data->eFarm['eFinancialYear']['endDate']);
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
		$cJournalCode = \journal\JournalCodeLib::deferred();
		$eJournalCode = \journal\JournalCodeLib::ask(POST('code', 'int'));
		$data->eOperation = new \journal\Operation([
			'account' => new \account\Account(),
			'thirdParty' => $eThirdParty,
			'cJournalCode' => $cJournalCode,
			'journalCode' => $eJournalCode
		]);
		$data->hasVatAccounting = \account\FinancialYearLib::getHasVatByFinancialYear($data->eFarm, $data->eFarm['eFinancialYear']);

		throw new ViewAction($data);

	})
	->post('doCreate', function($data) {

		$fw = new FailWatch();

		\journal\Operation::model()->beginTransaction();

		$accounts = post('account', 'array', []);

		if(count($accounts) === 0) {
			\Fail::log('Operation::allocate.accountsCheck');
		}

		$cOperation = \journal\OperationLib::prepareOperations($data->eFarm, $_POST);

		if($cOperation->empty() === TRUE) {
			\Fail::log('Operation::allocate.noOperation');
		}

		if($fw->ko()) {
			\journal\Operation::model()->rollBack();
		} else {
			\journal\Operation::model()->commit();
		}

		$fw->validate();

		$success = $cOperation->count() > 1 ? 'Operation::createdSeveral' : 'Operation::created';

		$hasMissingAsset = $cOperation->find(fn($e) => $e->acceptNewAsset())->notEmpty();
		if($hasMissingAsset) {
			throw new RedirectAction(\farm\FarmUi::urlConnected($data->eFarm).'/journal/livre-journal?hash='.$cOperation->first()['hash'].'&needsAsset=1&success=journal\\'.$success.'CreateAsset');
		}

		throw new RedirectAction(\farm\FarmUi::urlConnected($data->eFarm).'/journal/livre-journal?hash='.$cOperation->first()['hash'].'&success=journal\\'.$success);

	});

new \journal\OperationPage(function($data) {

	$data->eFarm->validate('canManage');

	if($data->eFarm->usesAccounting() === FALSE) {
		throw new RedirectAction('/comptabilite/parametrer?farm='.$data->eFarm['id']);
	}

	if(\company\CompanySetting::BETA and in_array($data->eFarm['id'], \company\CompanySetting::ACCOUNTING_FARM_BETA) === FALSE) {
		throw new RedirectAction('/comptabilite/beta?farm='.$data->eFarm['id']);
	}

})
	->get('createDocumentCollection', function($data) {

		$data->c = \journal\OperationLib::getByIds(GET('ids', 'array'));

		throw new ViewAction($data);

	})
	->writeCollection('doUpdatePaymentCollection', function($data) {

		$ePaymentMethod = \payment\MethodLib::getById(POST('paymentMethod'))
			->validateProperty('farm', $data->eFarm)
			->validate('canUse');

		$fw = new FailWatch();

		\journal\OperationLib::updatePaymentMethodCollection($data->c, $ePaymentMethod);

		$fw->validate();

		throw new ReloadAction('journal', 'Operations::updated');

	})
	->writeCollection('doUpdateJournalCollection', function($data) {

		$fw = new FailWatch();

		\journal\OperationLib::updateJournalCodeCollection($data->c, POST('journalCode', 'int'));

		$fw->validate();

		throw new ReloadAction('journal', 'Operations::updated');
	}, validate: [])
	->writeCollection('doUpdateDocumentCollection', function($data) {

		$document = POST('document');

		$fw = new FailWatch();

		\journal\OperationLib::updateDocumentCollection($data->c, $document);

		$fw->validate();

		throw new ReloadAction('journal', 'Operations::updated');
	}, validate: [])
	->write('doLock', function($data) {

		$nOperationLocked = \journal\OperationLib::lockUntil($data->e);

		throw new ReloadAction('journal', ($nOperationLocked > 1 ? 'Operation::groups.validated' : 'Operation::group.validated'));

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

	})
;
?>
