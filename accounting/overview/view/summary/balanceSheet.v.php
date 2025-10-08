<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'summary';
	$t->subNav = 'balanceSheet';

	$t->title = s("Le bilan de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlSummary($data->eFarm).'/balanceSheet';

	$t->mainTitle = new \overview\BalanceSheetUi()->getTitle();

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlSummary($data->eFarm).'/balanceSheet?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \overview\BalanceSheetUi()->getSearch($data->search, $data->eFinancialYear);
	echo new \overview\BalanceSheetUi()->getTable(
		eFarm: $data->eFarm,
		eFinancialYear: $data->eFinancialYear,
		cOperation: $data->cOperation,
		cOperationDetail: $data->cOperationDetail,
		result: $data->result,
		cAccount: $data->cAccount
	);

});
