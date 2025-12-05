<?php
new AdaptativeView(\farm\Farmer::BALANCE_SHEET, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'summary';

	$t->title = s("Le bilan de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/synthese/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingSummaryTitle($data->eFarm, $data->view);

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

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));

});

new AdaptativeView(\farm\Farmer::INCOME_STATEMENT, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'summary';

	$t->title = s("Le compte de résultat de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/synthese/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingSummaryTitle($data->eFarm, $data->view);

	echo new \overview\IncomeStatementUi()->getSearch(search: $data->search, cFinancialYear: $data->cFinancialYear, eFinancialYear: $data->eFinancialYear);
	echo new \overview\IncomeStatementUi()->getTable(
		eFarm: $data->eFarm,
		eFinancialYearComparison: $data->eFinancialYearComparison,
		eFinancialYear: $data->eFinancialYear,
		resultData: $data->resultData,
		cAccount: $data->cAccount,
		displaySummary: (bool)$data->search->get('view') === \overview\IncomeStatementLib::VIEW_DETAILED,
	);

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));

});

new AdaptativeView('noVat', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'summary';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/synthese/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingSummaryTitle($data->eFarm, $data->view);

	echo '<div class="util-info">';
		echo s("Cet exercice comptable n'a pas été configuré pour être assujetti à la TVA.");
		if($data->eFinancialYear['status'] === \account\FinancialYear::OPEN) {
			echo s("(<link>modifier les paramètres</link>).", ['link' => '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/:update?id='.$data->eFinancialYear['id'].'">']);
		} else {
			echo s("Les paramètres de l'exercice ne sont pas modifiables car il est terminé (<link>voir les exercices</link>).", ['link' => '<a href="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/">']);
		}
	echo '</div>';

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));

});

new AdaptativeView(\farm\Farmer::VAT, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'summary';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/synthese/declaration-de-tva';

	$t->mainTitle = new \farm\FarmUi()->getAccountingSummaryTitle($data->eFarm, $data->view);

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

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));

});

new AdaptativeView('operations', function($data, PanelTemplate $t) {

	return new \overview\VatUi()->showSuggestedOperations(
		$data->eFarm, $data->eFinancialYear, $data->eVatDeclaration, $data->cOperation, $data->cerfaCalculated, $data->cerfaDeclared,
	);

});
