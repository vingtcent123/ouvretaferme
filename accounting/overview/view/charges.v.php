<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les charges de {company}", ['company' => $data->eFarm['name']]);
	$t->tab = 'analyze';
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/charges';

	$t->mainTitle = new overview\AnalyzeUi()->getTitle($data->eFarm);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eFarm).'/charges?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new overview\ChargesUi()->get($data->cOperation, $data->cAccount);

});
