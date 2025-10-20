<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'summary';
	$t->subNav = 'vat/';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlSummary($data->eFarm).'/vat/';

	$t->mainTitle = new \overview\VatUi()->getTitle($data->cFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlSummary($data->eFarm).'/vat/?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);


});
