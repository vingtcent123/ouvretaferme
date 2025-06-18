<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les balances de {company}", ['company' => $data->eCompany['name']]);
	$t->tab = 'overview';
	$t->subNav = new \company\CompanyUi()->getOverviewSubNav($data->eCompany);
	$t->canonical = \company\CompanyUi::urlOverview($data->eCompany).'/balance';

	$t->mainTitle = new overview\OverviewUi()->getTitle($data->eCompany, $data->eFinancialYear);

	$t->mainYear = new \accounting\FinancialYearUi()->getFinancialYearTabs(
		function(\accounting\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlOverview($data->eCompany).'/balance?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	$t->package('main')->updateNavOverview($t->canonical, 'accounting');

	if(empty($data->accountingBalanceSheet) and empty($data->summaryAccountingBalance)) {

		echo '<div class="util-info">';
			echo s("Il n'y a pas d'information à afficher pour le moment.");
		echo '</div>';

	} else {

		echo '<div class="tabs-h" id="overview-accounting" onrender="'.encode('Lime.Tab.restore(this, "accounting-balance")').'">';

			echo '<div class="tabs-item">';
				echo '<a class="tab-item selected" data-tab="accounting-balance" onclick="Lime.Tab.select(this)">'.s("Balance comptable").'</a>';
				echo '<a class="tab-item" data-tab="accounting-summary" onclick="Lime.Tab.select(this)">'.s("Balance synthétique").'</a>';
			echo '</div>';

			echo '<div class="tab-panel" data-tab="accounting-balance">';
				echo new overview\AccountingUi()->displayAccountingBalanceSheet($data->accountingBalanceSheet);
			echo '</div>';

			echo '<div class="tab-panel" data-tab="accounting-summary">';
				echo new overview\AccountingUi()->displaySummaryAccountingBalance($data->summaryAccountingBalance);
			echo '</div>';

		echo '</div>';

	}
});

?>
