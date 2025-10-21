<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'summary';
	$t->subNav = 'vat/';

	$t->title = s("L'historique de TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlSummary($data->eFarm).'/vat/history';

	$t->mainTitle = new \overview\VatUi()->getHistoryTitle($data->eFarm, $data->cFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlSummary($data->eFarm).'/vat/history?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);


});
