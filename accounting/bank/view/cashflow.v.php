<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'bank';
	$t->subNav = 'cashflow';

	$t->title = s("Les opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlBank($data->eFarm).'/cashflow';

	$t->mainTitle = new \bank\BankUi()->getBankTitle($data->eFarm, $data->eFinancialYear, $data->nCashflow['all']['count']);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlBank($data->eFarm).'/cashflow?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	if($data->eFinancialYear->notEmpty()) {

		echo new \bank\CashflowUi()->getSearch($data->search, $data->eFinancialYear);
		echo new \bank\CashflowUi()->getSummarize($data->eFarm, $data->nCashflow, $data->search);
		echo new \bank\CashflowUi()->getCashflow($data->eFarm, $data->cCashflow, $data->eFinancialYear, $data->eImport, $data->search);

	} else {

		echo new \company\CompanyUi()->warnFinancialYear($data->eFarm, $data->cFinancialYear);

	}

});

new AdaptativeView('allocate', function($data, PanelTemplate $t) {

	return new \bank\CashflowUi()->getAllocate(
		$data->eFarm,
		$data->eFinancialYear,
		$data->eCashflow,
		$data->cPaymentMethod,
		$data->cJournalCode,
	);

});

new JsonView('addAllocate', function($data, AjaxTemplate $t) {

	$t->qs('#operation-create-list')->setAttribute('data-columns', $data->index + 1);
	$t->qs('.operation-create[data-index="'.($data->index - 1).'"]')->insertAdjacentHtml('afterend', new \bank\CashflowUi()->addAllocate(
		$data->eFarm, $data->eOperation, $data->eFinancialYear, $data->eCashflow, $data->index, cPaymentMethod: $data->cPaymentMethod));
	$t->qs('#add-operation')->setAttribute('post-index', $data->index + 1);
	if($data->index >= 4) {
		$t->qs('#add-operation')->addClass('not-visible');
	}
	$t->js()->eval('Cashflow.updateNewOperationLine('.$data->index.')');
	$t->js()->eval('Operation.showOrHideDeleteOperation()');

});

new AdaptativeView('attach', function($data, PanelTemplate $t) {

		return new \bank\CashflowUi()->getAttach($data->eFarm, $data->eCashflow, $data->cOperation);

});
?>
