<?php
new AdaptativeView('bank', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-accounting';
	$t->subNav = 'financials';

	$t->title = s("La trésorerie de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView;

	$t->mainTitle = new \overview\OverviewUi()->getFinancialsTitle($data->eFarm, $data->selectedView);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView.'?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new overview\BankUi()->get([$data->cOperationBank, $data->cOperationCash]);

});

new AdaptativeView('charges', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-accounting';
	$t->subNav = 'financials';

	$t->title = s("Les charges de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView;

	$t->mainTitle = new \overview\OverviewUi()->getFinancialsTitle($data->eFarm, $data->selectedView);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView.'?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new overview\ChargesUi()->get($data->cOperation, $data->cAccount);

});

new AdaptativeView('results', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-accounting';
	$t->subNav = 'financials';

	$t->title = s("Les charges de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView;

	$t->mainTitle = new \overview\OverviewUi()->getFinancialsTitle($data->eFarm, $data->selectedView);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView.'?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new overview\ResultUi()->getByMonth($data->eFinancialYear, $data->cOperation);


});
