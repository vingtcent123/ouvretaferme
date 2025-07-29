<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'bank';
	$t->subNav = 'cashflow';

	$t->title = s("Les opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlBank($data->eFarm).'/cashflow';

	$t->mainTitle = new \bank\BankUi()->getBankTitle($data->eFinancialYear);

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
		echo '<div class="mb-1"><span class="color-success" style="cursor: help">'.\Asset::icon('magic').'</span> : '.s("Une facture non payée correspondante a été trouvée.").'</div>';
		echo '<div class="util-block-help">'.s("Pour que le système trouve une correspondance entre une facture et une opération bancaire, il faut que les montants soient identiques à 1€ près, que le tiers existe et soit détecté dans le libellé de l'opération, et que <link>le client soit configuré dans le tiers</link>.", ['link' => '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/thirdParty">']).'</div>';
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
		$data->cInvoice,
		['grant' => $data->cAssetGrant, 'asset' => $data->cAssetToLinkToGrant],
		$data->cPaymentMethod,
	);

});

new JsonView('addAllocate', function($data, AjaxTemplate $t) {

	$t->qs('#create-operation-list')->setAttribute('data-columns', $data->index + 1);
	$t->qs('.create-operation[data-index="'.($data->index - 1).'"]')->insertAdjacentHtml('afterend', new \bank\CashflowUi()->addAllocate($data->eFarm, $data->eOperation, $data->eFinancialYear, $data->eCashflow, $data->index, ['grant' => $data->cAssetGrant, 'asset' => $data->cAssetToLinkToGrant]));
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
