<?php
new AdaptativeView(\farm\Farmer::CHARGES, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("Les charges de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/'.$data->view;

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

	$t->js()->replaceHistory($t->canonical);

});

new AdaptativeView(\farm\Farmer::BANK, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("La trésorerie de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/analyze/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo new overview\BankUi()->get($data->ccOperationBank, $data->ccOperationCash);

	$t->js()->replaceHistory($t->canonical);

});

new AdaptativeView(\farm\Farmer::SIG, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("Le solde intermédiaire de gestion de de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/analyze/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo new \overview\SigUi()->getSearch(search: $data->search, cFinancialYear: $data->cFinancialYear, eFinancialYear: $data->eFinancialYear);
	echo new \overview\SigUi()->display(
		eFarm: $data->eFarm,
		values: $data->values,
		eFinancialYear: $data->eFinancialYear,
		eFinancialYearComparison: $data->eFinancialYearComparison,
	);

	$t->js()->replaceHistory($t->canonical);

});

new AdaptativeView(\farm\Farmer::BALANCE_SHEET, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("Le bilan de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo new \overview\BalanceSheetUi()->getSearch(
		search        : $data->search,
		cFinancialYear: $data->cFinancialYear,
		eFinancialYear: $data->eFinancialYear,
	);

	echo new \overview\BalanceSheetUi()->getTable(
		eFarm                   : $data->eFarm,
		eFinancialYear          : $data->eFinancialYear,
		eFinancialYearComparison: $data->eFinancialYearComparison,
		balanceSheetData        : $data->balanceSheetData,
		totals                  : $data->totals,
		cAccount                : $data->cAccount,
		hasDetail               : $data->search->get('view') === \overview\BalanceSheetLib::VIEW_DETAILED,
	);


});

new AdaptativeView(\farm\Farmer::INCOME_STATEMENT, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("Le compte de résultat de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo new \overview\IncomeStatementUi()->getSearch(search: $data->search, cFinancialYear: $data->cFinancialYear, eFinancialYear: $data->eFinancialYear);
	echo new \overview\IncomeStatementUi()->getTable(
		eFarm: $data->eFarm,
		eFinancialYearComparison: $data->eFinancialYearComparison,
		eFinancialYear: $data->eFinancialYear,
		resultData: $data->resultData,
		cAccount: $data->cAccount,
		displaySummary: (bool)$data->search->get('view') === \overview\IncomeStatementLib::VIEW_DETAILED,
	);


});

new AdaptativeView('noVat', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo '<div class="util-info">';
	echo s("Cet exercice comptable n'a pas été configuré pour être assujetti à la TVA.");
	if($data->eFinancialYear['status'] === \account\FinancialYear::OPEN) {
		echo s("(<link>modifier les paramètres</link>).", ['link' => '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/:update?id='.$data->eFinancialYear['id'].'">']);
	} else {
		echo s("Les paramètres de l'exercice ne sont pas modifiables car il est terminé (<link>voir les exercices</link>).", ['link' => '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/">']);
	}
	echo '</div>';


});

new AdaptativeView(\farm\Farmer::VAT, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/declaration-de-tva';

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo '<div class="tabs-h" id="vat">';

	echo new \overview\VatUi()->getVatTabs($data->eFarm, $data->eFinancialYear, $data->tab);

	switch($data->tab) {

		case NULL:
			echo new \overview\VatUi()->getGeneralTab($data->eFarm, $data->eFinancialYear, $data->vatParameters);
			break;

		case 'journal-buy':
		case 'journal-sell':
			echo new \overview\VatUi()->getOperationsTab($data->eFarm, mb_substr($data->tab, mb_strlen('journal') + 1), $data->cOperation, $data->vatParameters);
			break;

		case 'check':
			echo new \overview\VatUi()->getCheck($data->eFarm, $data->check, $data->vatParameters);
			break;

		case 'cerfa':
			echo new \overview\VatUi()->getCerfa($data->eFarm, $data->eFinancialYear, $data->cerfa, $data->precision, $data->vatParameters);
			break;

		case 'history':
			echo new \overview\VatDeclarationUi()->getHistory($data->eFarm, $data->eFinancialYear, $data->cVatDeclaration, $data->allPeriods);
			break;
	}

	echo '</div>';


});

new AdaptativeView('operations', function($data, PanelTemplate $t) {

	return new \overview\VatUi()->showSuggestedOperations(
		$data->eFarm, $data->eFinancialYear, $data->eVatDeclaration, $data->cOperation, $data->cerfaCalculated, $data->cerfaDeclared,
	);

});
