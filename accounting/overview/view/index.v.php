<?php
new AdaptativeView(\overview\AnalyzeLib::TAB_CHARGES, function($data, FarmTemplate $t) {

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
		echo new overview\ResultUi()->getByMonth($data->eFarm['eFinancialYear'], $data->cOperation);
	echo '</div>';

	echo '</div>';

});

new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("L'exercice comptable {year} de {farm}", ['year' => $data->eFarm['eFinancialYear']->getLabel(), 'farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/';

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, \overview\AnalyzeLib::TAB_FINANCIAL_YEAR);

	Asset::css('account', 'financialYear.css');

	echo new \account\FinancialYearUi()->view($data->eFarm, $data->eFinancialYear);

	if($data->eFinancialYear['nOperation'] === 0) {

		echo '<div class="util-empty">';
			echo '<p>'.s("Dès que vous aurez créé vos premières écritures dans cet exercice comptable, vous pourrez éditer vos documents comme les bilans, le compte de résultat...").'</p>';
			echo '<p>'.s("Voici plusieurs manières d'ajouter des écritures à votre exercice comptable :").'</p>';
		echo '</div>';

		echo '<div class="util-buttons">';

			echo '<a href="'.\company\CompanyUi::urlJournal($data->eFarm).'/livre-journal" class="util-button">';

				echo '<h4>'.s("Créer une écriture depuis le journal").'</h4>';
				echo \Asset::icon('journal-bookmark');

			echo '</a>';

			echo '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations" class="util-button">';

				echo '<h4>'.s("Créer une écriture depuis mes opérations bancaires").'</h4>';
				echo \Asset::icon('piggy-bank');

			echo '</a>';

			echo '<a href="'.\company\CompanyUi::urlFarm($data->eFarm, $data->eFinancialYear).'/etats-financiers/" class="util-button">';

				echo '<h4>'.s("Importer un fichier FEC depuis les paramètres de l'exercice").'</h4>';
				echo \Asset::icon('gear');

			echo '</a>';

		echo '</div>';

	} else {

		echo '<h3>'.s("Tous  les documents de l'exercice").'</h3>';
		echo new \account\FinancialYearDocumentUi()->list($data->eFarm, $data->eFinancialYear);

	}

});

new AdaptativeView(\overview\AnalyzeLib::TAB_BANK, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("La trésorerie de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo new overview\BankUi()->get($data->ccOperationBank, $data->ccOperationCash);

});

new AdaptativeView(\overview\AnalyzeLib::TAB_SIG, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("Les soldes intermédiaires de gestion de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view, $data->eFinancialYearDocument);

	echo new \overview\SigUi()->getSearch(search: $data->search, cFinancialYear: $data->eFarm['cFinancialYear'], eFinancialYear: $data->eFarm['eFinancialYear']);

	if(empty($data->values[$data->eFarm['eFinancialYear']['id']])) {

		echo '<div class="util-empty">';
			echo s("Le suivi des Soldes Intermédiaires de Gestion sera disponible lorsque vous aurez créé des écritures pour cet exercice.");
		echo '</div>';

	} else {

		echo new \overview\SigUi()->display(
			eFarm: $data->eFarm,
			values: $data->values,
			eFinancialYear: $data->eFarm['eFinancialYear'],
			eFinancialYearComparison: $data->eFinancialYearComparison,
		);

	}

});

new AdaptativeView(\overview\AnalyzeLib::TAB_BALANCE_SHEET, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("Le bilan de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view, $data->eFinancialYearDocument, count($data->balanceSheetData) > 0);

	if(count($data->balanceSheetData) === 0) {

		echo '<div class="util-empty">';
			echo s("Le bilan sera disponible lorsque vous aurez créé des écritures pour cet exercice.");
		echo '</div>';

	} else {

		if($data->eFinancialYearPrevious->notEmpty() and $data->eFarm['eFinancialYear']['openDate'] === NULL) {

			echo '<div class="util-block-danger">';
				echo '<p>⚠️ '.s("️Le bilan d'ouverture n'a pas encore été réalisé, les données ne sont donc pas fiables.").'</p>';
				echo '<a class="btn btn-md btn-primary" href="'.\company\CompanyUi::urlAccount($data->eFarm).'/financialYear/:open?id='.$data->eFarm['eFinancialYear']['id'].'">';
					echo s("Réaliser le bilan d'ouverture maintenant");
				echo '</a>';
			echo '</div>';
		}

		echo new \overview\BalanceSheetUi()->getSearch(
			search        : $data->search,
			cFinancialYear: $data->eFarm['cFinancialYear'],
			eFinancialYear: $data->eFarm['eFinancialYear'],
		);

		echo new \overview\BalanceSheetUi()->getTable(
			eFarm                   : $data->eFarm,
			eFinancialYear          : $data->eFarm['eFinancialYear'],
			eFinancialYearComparison: $data->eFinancialYearComparison,
			balanceSheetData        : $data->balanceSheetData,
			totals                  : $data->totals,
			cAccount                : $data->cAccount,
			hasDetail               : $data->search->get('view') === \overview\BalanceSheetLib::VIEW_DETAILED,
		);

	}

});

new AdaptativeView(\overview\AnalyzeLib::TAB_INCOME_STATEMENT, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("Le compte de résultat de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/'.$data->view;

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view, $data->eFinancialYearDocument, count($data->resultData) > 0);

	if(count($data->resultData) === 0) {

		echo '<div class="util-empty">';
			echo s("Le compte de résultat sera disponible lorsque vous aurez créé des écritures pour cet exercice.");
		echo '</div>';

	} else {

		echo new \overview\IncomeStatementUi()->getSearch(search: $data->search, cFinancialYear: $data->eFarm['cFinancialYear'], eFinancialYear: $data->eFarm['eFinancialYear']);

		echo new \overview\IncomeStatementUi()->getTable(
			eFarm: $data->eFarm,
			eFinancialYearComparison: $data->eFinancialYearComparison,
			eFinancialYear: $data->eFarm['eFinancialYear'],
			resultData: $data->resultData,
			cAccount: $data->cAccount,
			displaySummary: (bool)$data->search->get('view') === \overview\IncomeStatementLib::VIEW_DETAILED,
		);

	}

});

new AdaptativeView(\overview\AnalyzeLib::TAB_VAT, function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'analyze';

	$t->title = s("La TVA de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/declaration-de-tva';

	$t->mainTitle = new \farm\FarmUi()->getAccountingFinancialsTitle($data->eFarm, $data->view);

	echo '<div class="tabs-h" id="vat">';

	echo new \overview\VatUi()->getVatTabs($data->eFarm, $data->eFarm['eFinancialYear'], $data->tab);

	switch($data->tab) {

		case NULL:
			echo new \overview\VatUi()->getGeneralTab($data->eFarm, $data->eFarm['eFinancialYear'], $data->vatParameters);
			break;

		case 'journal-buy':
		case 'journal-sell':
			echo new \overview\VatUi()->getOperationsTab($data->eFarm, mb_substr($data->tab, mb_strlen('journal') + 1), $data->cOperation, $data->vatParameters);
			break;

		case 'check':
			echo new \overview\VatUi()->getCheck($data->eFarm, $data->check, $data->vatParameters);
			break;

		case 'cerfa':
			echo new \overview\VatUi()->getCerfa($data->eFarm, $data->eFarm['eFinancialYear'], $data->cerfa, $data->precision, $data->vatParameters);
			break;

		case 'history':
			echo new \overview\VatDeclarationUi()->getHistory($data->eFarm, $data->eFarm['eFinancialYear'], $data->cVatDeclaration, $data->allPeriods);
			break;
	}

	echo '</div>';


});

new AdaptativeView('operations', function($data, PanelTemplate $t) {

	return new \overview\VatUi()->showSuggestedOperations(
		$data->eFarm, $data->eFarm['eFinancialYear'], $data->eVatDeclaration, $data->cOperation, $data->cerfaCalculated, $data->cerfaDeclared,
	);

});
