<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-accounting';
	$t->subNav = 'sig';

	$t->title = s("Le solde intermédiaire de gestion de de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlAnalyze($data->eFarm, 'sig');

	$t->mainTitle = new \overview\SigUi()->getTitle($data->cFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlAnalyze($data->eFarm, 'sig').'?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \overview\SigUi()->getSearch(search: $data->search, cFinancialYear: $data->cFinancialYear, eFinancialYear: $data->eFinancialYear);
	echo new \overview\SigUi()->display(
		eFarm: $data->eFarm,
		values: $data->values,
		eFinancialYear: $data->eFinancialYear,
		eFinancialYearComparison: $data->eFinancialYearComparison,
	);


});
