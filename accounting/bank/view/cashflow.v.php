<?php
new AdaptativeView('/banque/operations', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Les opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/banque/operations';

	$t->mainTitle = new \farm\FarmUi()->getAccountingBankTitle($data->eFarm, 'bank', $data->nSuggestion, $data->nCashflow['all']['count']);

	echo new \bank\CashflowUi()->getSearch($data->search, $data->cFinancialYear, $data->minDate, $data->maxDate);
	echo new \bank\CashflowUi()->getSummarize($data->eFarm, $data->nCashflow, $data->search);
	echo new \bank\CashflowUi()->getCashflow($data->eFarm, $data->cCashflow, $data->eFinancialYear, $data->eImport, $data->search);

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));

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

		return new \bank\CashflowUi()->getAttach($data->eFarm, $data->eCashflow, $data->eThirdParty, $data->cOperation);

});
?>
