<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'summary';
	$t->subNav = 'incomeStatement';

	$t->title = s("Le compte de résultat de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlSummary($data->eFarm).'/incomeStatement';

	$t->mainTitle = new \overview\IncomeStatementUi()->getTitle();

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlSummary($data->eFarm).'/incomeStatement?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \overview\IncomeStatementUi()->getSearch(search: $data->search, cFinancialYear: $data->cFinancialYear, eFinancialYear: $data->eFinancialYear);
	echo new \overview\IncomeStatementUi()->getTable(
		eFarm: $data->eFarm,
		eFinancialYearComparison: $data->eFinancialYearComparison,
		eFinancialYear: $data->eFinancialYear,
		resultData: $data->resultData,
		cAccount: $data->cAccount,
		displaySummary: (bool)$data->search->get('view') === \overview\IncomeStatementLib::VIEW_DETAILED,
	);


});
