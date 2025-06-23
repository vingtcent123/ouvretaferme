<?php
new AdaptativeView('bank', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-accounting';
	$t->subNav = 'financials';

	$t->title = s("La trésorerie de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView;

	$categories = new \company\CompanyUi()->getAnalyzeCategories($data->eFarm);
	$t->mainTitle = \company\CompanyUi::getDropdownMenuTitle($categories, $data->selectedView);
	$t->mainTitleClass = 'hide-lateral-down';

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

	$t->title = s("Les charges de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView;

	$categories = new \company\CompanyUi()->getAnalyzeCategories($data->eFarm);
	$t->mainTitle = \company\CompanyUi::getDropdownMenuTitle($categories, $data->selectedView);
	$t->mainTitleClass = 'hide-lateral-down';

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

	$t->title = s("Les charges de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView;

	$categories = new \company\CompanyUi()->getAnalyzeCategories($data->eFarm);
	$t->mainTitle = \company\CompanyUi::getDropdownMenuTitle($categories, $data->selectedView);
	$t->mainTitleClass = 'hide-lateral-down';

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eFarm).'/financials:'.$data->selectedView.'?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo '<div class="tabs-h" id="analyze-result" onrender="'.encode('Lime.Tab.restore(this, "result-month")').'">';

	echo '<div class="tabs-item">';
	echo '<a class="tab-item selected" data-tab="result-month" onclick="Lime.Tab.select(this)">'.s("Mois par mois").'</a>';
	echo '<a class="tab-item" data-tab="result-all" onclick="Lime.Tab.select(this)">'.s("Compte de résultat").'</a>';
	echo '</div>';

	echo '<div class="tab-panel" data-tab="result-month">';
	echo new overview\ResultUi()->getByMonth($data->eFinancialYear, $data->cOperation);
	echo '</div>';

	echo '<div class="tab-panel" data-tab="result-all">';
	echo new overview\ResultUi()->get($data->result, $data->cAccount);
	echo '</div>';

	echo '</div>';


});
