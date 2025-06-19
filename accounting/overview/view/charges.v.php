<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les charges de {company}", ['company' => $data->eCompany['name']]);
	$t->tab = 'analyze';
	$t->canonical = \company\CompanyUi::urlOverview($data->eCompany).'/charges';

	$t->mainTitle = new overview\AnalyzeUi()->getTitle($data->eCompany);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eCompany).'/charges?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	$t->package('main')->updateNavAnalyze($t->canonical, 'charges');

	echo new overview\ChargesUi()->get($data->eCompany, $data->eFinancialYear, $data->cOperation, $data->cAccount);

});
