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

	echo new \overview\IncomeStatementUi()->getTable($data->eFarm, $data->eFinancialYearPrevious, $data->eFinancialYear, $data->resultData, $data->cAccount);


});
