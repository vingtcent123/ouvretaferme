<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-accounting';
	$t->subNav = 'finance';

	$t->title = s("La trésorerie de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlAnalyze($data->eFarm, 'finance');

	$t->mainTitle = '<h1>'.s("Suivi de la trésorerie").'</h1>';

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlAnalyze($data->eFarm, 'finance').'?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new overview\BankUi()->get($data->ccOperationBank, $data->ccOperationCash);

});
