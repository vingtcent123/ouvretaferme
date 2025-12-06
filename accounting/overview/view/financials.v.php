<?php
new AdaptativeView(\farm\Farmer::CHARGES, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'financials';

	$t->title = s("Les charges de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/gestion/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

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

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));
	$t->js()->replaceHistory($t->canonical);
	
});

new AdaptativeView(\farm\Farmer::BANK, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'financials';

	$t->title = s("La trésorerie de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/gestion/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo new overview\BankUi()->get($data->ccOperationBank, $data->ccOperationCash);

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));
	$t->js()->replaceHistory($t->canonical);
	
});

new AdaptativeView(\farm\Farmer::INTERMEDIATE, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'financials';

	$t->title = s("Le solde intermédiaire de gestion de de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/gestion/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo new \overview\SigUi()->getSearch(search: $data->search, cFinancialYear: $data->cFinancialYear, eFinancialYear: $data->eFinancialYear);
	echo new \overview\SigUi()->display(
		eFarm: $data->eFarm,
		values: $data->values,
		eFinancialYear: $data->eFinancialYear,
		eFinancialYearComparison: $data->eFinancialYearComparison,
	);

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));
	$t->js()->replaceHistory($t->canonical);
	
});
