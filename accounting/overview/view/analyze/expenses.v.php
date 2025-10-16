<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'analyze-accounting';
	$t->subNav = 'expenses';

	$t->title = s("Les charges de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlAnalyze($data->eFarm, 'expenses');

	$t->mainTitle = '<h1>'.s("Charges et résultat").'</h1>';

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlAnalyze($data->eFarm, 'expenses').'?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo '<div class="tabs-h" id="analyze-bank" onrender="'.encode('Lime.Tab.restore(this, "expenses")').'">';

		echo '<div class="tabs-item">';
			echo '<a class="tab-item selected text-center" data-tab="expenses" onclick="Lime.Tab.select(this)">'.s("Charges").'</a>';
			echo '<a class="tab-item text-center" data-tab="income" onclick="Lime.Tab.select(this)">'.s("Résultat").'</a>';
		echo '</div>';

		echo '<div class="tab-panel" data-tab="expenses">';
			echo new overview\ChargesUi()->get($data->cOperation, $data->cAccount);
		echo '</div>';
		echo '<div class="tab-panel" data-tab="income">';
			echo new overview\ResultUi()->getByMonth($data->eFinancialYear, $data->cOperation);
		echo '</div>';
	echo '</div>';


});
