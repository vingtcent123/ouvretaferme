<?php
new AdaptativeView('/banque/operations', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Les opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/banque/operations';

	$t->mainTitle = new \farm\FarmUi()->getAccountingBankTitle($data->eFarm, 'bank', $data->nCashflow);

	echo new \bank\CashflowUi()->getSearch($data->eFarm, $data->search, $data->minDate, $data->maxDate);

	echo new \bank\CashflowUi()->getReconciliateInfo($data->eFarm, $data->eImportCurrent);

	echo new \bank\CashflowUi()->getSummarize($data->eFarm, $data->nSuggestion);

	if($data->cBankAccount->count() > 1) {
		echo new \bank\CashflowUi()->getTabs($data->eFarm, $data->cBankAccount, $data->search->get('bankAccount'));
	}

	echo new \bank\CashflowUi()->list($data->eFarm, $data->cCashflow, $data->eFarm['eFinancialYear'], $data->eImport, $data->search, $data->eFarm['cFinancialYear']);
	echo \util\TextUi::pagination($data->page, $data->nPage);


});

new AdaptativeView('allocate', function($data, PanelTemplate $t) {

	return new \bank\CashflowUi()->getAllocate(
		$data->eFarm,
		$data->eFarm['eFinancialYear'],
		$data->eCashflow,
		$data->cPaymentMethod,
		$data->cJournalCode,
	);

});

new JsonView('addAllocate', function($data, AjaxTemplate $t) {

	$t->qs('#operation-create-list')->setAttribute('data-columns', $data->index + 1);
	$t->qs('.operation-create[data-index="'.($data->index - 1).'"]')->insertAdjacentHtml('afterend', new \bank\CashflowUi()->addAllocate(
		$data->eFarm, $data->eOperation, $data->eFarm['eFinancialYear'], $data->eCashflow, $data->index, cPaymentMethod: $data->cPaymentMethod));
	$t->qs('#add-operation')->setAttribute('post-index', $data->index + 1);
	if($data->index >= 4) {
		$t->qs('#add-operation')->addClass('not-visible');
	}
	$t->js()->eval('Cashflow.updateNewOperationLine('.$data->index.')');
	$t->js()->eval('Operation.showOrHideDeleteOperation()');

});

new AdaptativeView('attach', function($data, PanelTemplate $t) {

	return new \bank\CashflowUi()->getAttach($data->eFarm, $data->eCashflow, $data->eThirdParty, $data->tip);

});

new JsonView('calculateAttach', function($data, AjaxTemplate $t) {

	if($data->cOperation->count() === 0) {

		$t->qs('#cashflow-attach-information')->innerHtml('');
		$t->qs('[data-field="totalAmount"]')->innerHtml(\util\TextUi::money(0));
		$t->qs('#cashflow-operations')->addClass('hide');

	} else {

		// Total des écritures autres que 512
		$cOperationOther = $data->cOperation->find(fn($e) => \account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS) === FALSE);
		$totalOther = round($cOperationOther->sum(fn($e) => $e['type'] === \journal\Operation::CREDIT ? -1 * $e['amount'] : $e['amount']), 2);

		// Total des écritures de banque
		$cOperationBank = $data->cOperation->find(fn($e) => \account\AccountLabelLib::isFromClass($e['accountLabel'], \account\AccountSetting::BANK_ACCOUNT_CLASS));
		$totalBank = round($cOperationBank->sum(fn($e) => $e['type'] === \journal\Operation::CREDIT ? -1 * $e['amount'] : $e['amount']), 2);

		$amountCashflow = round($data->eCashflow['amount'], 2);
		if($amountCashflow + $totalBank === (-1) * $totalOther) {

			if($totalBank === 0) {

				$t->qs('#cashflow-attach-information')->innerHtml('<div class="util-success">'.Asset::icon('fire').' '.s("Vos écritures sont équilibrées.").'</div>');

			} else {

				$t->qs('#cashflow-attach-information')->innerHtml('<div class="util-success">'.Asset::icon('fire').' '.s("Vous équilibrez ainsi les écritures précédentes !").'</div>');

			}

		} else {


			$t->qs('#cashflow-attach-information')->innerHtml('<div class="util-warning-outline">'.Asset::icon('exclamation-triangle').' '.s("Les montants ne sont pas équilibrés, mais vous pourrez quand même valider.").'</div>');

		}

		$amount = 0;
		foreach($data->cOperationSelected as $eOperation) {
			$amount += $eOperation['type'] === \journal\Operation::DEBIT ? -1 * $eOperation['amount'] : $eOperation['amount'];
			foreach($eOperation['cOperationHash'] as $eOperationHash) {
				if($eOperationHash->is($eOperation) === FALSE) {
					$amount += $eOperationHash['type'] === \journal\Operation::DEBIT ?  -1 * $eOperationHash['amount'] : $eOperationHash['amount'];
				}
			}
		}
		$amount = round($amount, 2);

		$t->qs('[data-field="totalAmount"]')->innerHtml(\util\TextUi::money(abs($amount)));

		$t->qs('div[data-operations]')->innerHtml(new \bank\CashflowUi()->getSelectedOperationsTableForAttachement($data->eCashflow, $data->cOperationSelected));

	}

});
?>
