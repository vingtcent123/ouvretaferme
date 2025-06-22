<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("La trésorerie de {farm}", ['farm' => $data->eFarm['name']]);
	$t->tab = 'analyze';
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/bank';

	$t->mainTitle = new overview\AnalyzeUi()->getTitle($data->eFarm);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eFarm).'/bank?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new overview\BankUi()->get([$data->cOperationBank, $data->cOperationCash]);

});
