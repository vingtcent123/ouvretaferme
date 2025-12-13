<?php
new AdaptativeView('/precomptabilite', function($data, FarmTemplate $t) {

	$t->title = s("Précomptabilité de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite';

	$t->nav = 'preaccounting';

	$t->mainTitle = new \farm\FarmUi()->getPreAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'prepare', ['import' => array_sum($data->counts['import']), 'reconciliate-sales' => $data->counts['reconciliate']['sales'], 'reconciliate-operations' => $data->counts['reconciliate']['operations']]);

	$hasSearch = ($data->search->get('from') and $data->search->get('to'));
	$inFuture = ($data->search->get('from') > date('Y-m-d'));

	$errors = $data->nProduct + $data->nItem + $data->nSaleDelivered + $data->nSalePayment + $data->nSaleClosed;
	$step = 1;

	echo '<div class="util-block">';
	echo '<h3>'.s("Choix de la période").'</h3>';
	echo new \selling\AccountingUi()->getSearch($data->eFarm, $data->search);
	echo '</div>';

	$title = Asset::icon('1-circle').' '.s("Vérification des références de produits");
	echo '<h3 class="mt-2">';

	if($inFuture) {

		echo $title;

	} else if($data->nProduct === 0) {

		echo $title;
		if($hasSearch) {
			echo '<span class="color-success ml-1">'.Asset::icon('check-circle').'</span>';
		}

	} else {

		echo '<a class="sale-preaccounting-title" data-url="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite/product" data-step="product" onclick="SellingAccounting.toggle(\'product\'); return true;">';
		echo $title;
		echo '<span class="bg-warning tab-item-count ml-1">'.$data->nProduct.'</span>';
		echo Asset::icon('chevron-down', ['class' => 'ml-1']);
		echo '</a>';
	}

	echo '</h3>';

	echo '<div data-step-container="product" class="hide">';
	echo '<div class="util-block-help">';
	echo s("Les comptes associés aux produits sont automatiquement répercutés sur les articles référencés par ces produits dans vos ventes. Ainsi, votre export de précomptabilité sera le plus juste possible et vous économisera de fastidieux contrôles à l'import dans votre comptabilité.");
	echo '<br />';
	echo s("Pour aller plus vite, vous pouvez modifier vos produits en masse grâce aux cases à cocher.");
	echo '</div>';
	echo '</div>';

	echo '<div data-step="product"></div>';

	$step++;

	$title = Asset::icon('2-circle').' '.s("Vérification des articles vendus");
	echo '<h3 class="mt-2">';

	if($inFuture) {

		echo $title;

	} else if($data->nItem === 0) {

		echo $title;
		if($hasSearch) {
			echo '<span class="color-success ml-1">'.Asset::icon('check-circle').'</span>';
		}

	} else {

		echo '<a class="sale-preaccounting-title" data-url="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite/item" data-step="item" onclick="SellingAccounting.toggle(\'item\'); return true;">';
		echo $title;
		echo '<span class="bg-warning tab-item-count ml-1">'.$data->nItem.'</span>';
		echo Asset::icon('chevron-down', ['class' => 'ml-1']);
		echo '</a>';

	}

	echo '</h3>';

	echo '<div data-step-container="item" class="hide">';
	echo '<div class="util-block-help">';
	echo s("En associant des classes de compte à vos articles vendus, votre export de précomptabilité sera le plus juste possible et vous économisera de fastidieux contrôles à l'import dans votre comptabilité.");
	echo '<br />';
	echo s("Pour aller plus vite, vous pouvez modifier vos articles en masse grâce aux cases à cocher (individuellement ou par vente).");
	echo '</div>';
	echo '</div>';

	echo '<div data-step="item"></div>';

	$step++;

	foreach([
		'delivered' => $data->nSaleDelivered,
		'payment' => $data->nSalePayment,
		'closed' => $data->nSaleClosed,
	] as $key => $number) {

		echo '<h3 class="mt-2">';

		$title = Asset::icon($step.'-circle').' ';
		$title .= match($key) {
			'delivered' => s("Vérification des ventes livrées"),
			'payment' => s("Vérification des moyens de paiement des ventes"),
			'closed' => s("Vérification des ventes clôturées"),
		};

		if($inFuture) {

			echo $title;;

		} else if($data->nSaleDelivered === 0) {

			echo $title;;
			if($hasSearch) {
				echo '<span class="color-success ml-1">'.Asset::icon('check-circle').'</span>';
			}

		} else {

			echo '<a class="sale-preaccounting-title" data-url="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite/'.$key.'" data-step="'.$key.'" onclick="SellingAccounting.toggle(\''.$key.'\'); return true;">';
			echo $title;;
			echo '<span class="bg-warning tab-item-count ml-1">'.$number.'</span>';
			echo Asset::icon('chevron-down', ['class' => 'ml-1']);
			echo '</a>';

		}

		echo '</h3>';

		echo '<div data-step-container="'.$key.'" class="hide">';
		echo '<div class="util-block-help">';
		if($key === 'delivered') {

			echo '<p>'.s("Les ventes doivent être considérées comme livrées pour pouvoir être importées en comptabilité. La date de livraison sera celle enregistrée pour l'écriture comptable.").'</p>';

		} else if($key === 'payment') {

			echo '<p>'.s("Renseignez ici le moyen de paiement qui a été utilisé dans vos ventes pour transférer cette information automatiquement dans votre comptabilité").'</p>';
			echo '<p>'.s("Ces ventes seront également <b>automatiquement marquées comme payées</b>.").'</p>';

		} else {

			echo '<p>'.s("Une vente clôturée n'est plus modifiable. Ainsi, votre import en comptabilité reflètera ce qui a été réellement enregistré au niveau de la facturation et respectera la réglementation en vigueur.").'</p>';

		}
		echo '</div>';
		echo '</div>';
		echo '<div data-step="'.$key.'"></div>';

		$step++;

	}

	$key = 'export';
	echo '<h3 class="mt-2">';
	echo '<a class="sale-preaccounting-title" data-step="'.$key.'" onclick="SellingAccounting.toggle(\''.$key.'\'); return true;">';
	echo Asset::icon($step.'-circle').' '.s("Exporter");
	echo Asset::icon('chevron-down', ['class' => 'ml-1']);
	echo '</a>';
	echo '</h3>';

	$form = new \util\FormUi();
	if($data->isSearchValid) {

		$attributes = [
			'href' => \farm\FarmUi::urlSellingSalesAccounting($data->eFarm).':fec?from='.$data->search->get('from').'&to='.$data->search->get('to'),
			'data-ajax-navigation' => 'never',
		];
		$class = ($errors > 0 ? 'btn-warning' : 'btn-secondary');

	} else {
		$attributes = [
			'href' => 'javascript: void(0);',
		];
		$class = 'btn-secondary disabled';
	}

	echo '<div data-step-container="'.$key.'" class="hide">';
	echo new \selling\AccountingUi()->explainExport($data->eFarm);
	echo '</div>';

	echo '<div data-step="'.$key.'" class="hide">';
	echo '<a '.attrs($attributes).' style="height: 100%;">'.$form->button(s("Exporter"), ['class' => 'btn '.$class]).'</a>';
	echo '</div>';

});

new JsonView('/precomptabilite/{type}', function($data, AjaxTemplate $t) {

	switch($data->type) {

		case 'product':
			$t->qs('div[data-step="product"]')->innerHtml(new \selling\AccountingUi()->products($data->eFarm, $data->cProduct, $data->nToCheck, $data->nVerified));
			break;

		case 'item':
			$t->qs('div[data-step="item"]')->innerHtml(new \selling\AccountingUi()->items($data->cItem, $data->nToCheck, $data->nVerified));
			break;

		case 'delivered':
		case 'payment':
		case 'closed':
			$t->qs('div[data-step="'.$data->type.'"]')->innerHtml(
				new \selling\AccountingUi()->sales($data->eFarm, $data->type, $data->cSale, $data->cPaymentMethod, $data->nToCheck, $data->nVerified)
			);
			break;
	}

	$t->qs('div[data-step-container="'.$data->type.'"]')->removeHide();
});


new AdaptativeView('/precomptabilite:importer', function($data, FarmTemplate $t) {

	$t->nav = 'preaccounting';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer';

	$t->mainTitle = new \farm\FarmUi()->getPreAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'import', ['import' => array_sum($data->counts['import']), 'reconciliate-sales' => $data->counts['reconciliate']['sales'], 'reconciliate-operations' => $data->counts['reconciliate']['operations']]);

	echo '<div class="util-block-help">';
	echo '<p>'.s("Cette page vous permet de vérifier et importer vos ventes depuis le module de commercialisation directement en comptabilité.").'</p>';
	echo '<p>'.s("Si des ventes n'apparaissent pas, vérifiez si les données de vos ventes sont bien préparées pour la comptabilité sur <link>cette page</link>.", ['link' => '<a href="'.\farm\FarmUi::urlSellingSalesAccounting($data->eFarm).'&from='.$data->eFinancialYear['startDate'].'&to='.$data->eFinancialYear['endDate'].'">']).'</p>';
	echo '</div>';

	echo '<div class="tabs-item">';

	foreach(['market', 'invoice', 'sales'] as $tab) {

		echo '<a class="tab-item '.($data->selectedTab === $tab ? ' selected' : '').'" data-tab="'.$tab.'" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer?tab='.$tab.'">';
		echo match($tab) {
			'market' => s("Marchés"),
			'invoice' => s("Factures"),
			'sales' => s("Autres ventes"),
		};
		echo ' <small class="tab-item-count">'.$data->counts['import'][$tab].'</small>';
		echo '</a>';

	}

	echo '</div>';

	echo match($data->selectedTab) {
		'market' => new \preaccounting\ImportUi()->displayMarket($data->eFarm, $data->eFinancialYear, $data->c),
		'invoice' => new \preaccounting\ImportUi()->displayInvoice($data->eFarm, $data->eFinancialYear, $data->c, $data->search),
		'sales' => new \preaccounting\ImportUi()->displaySales($data->eFarm, $data->eFinancialYear, $data->c, $data->search),
	};

});

new AdaptativeView('/precomptabilite:rapprocher-ventes', function($data, FarmTemplate $t) {

	$t->nav = 'preaccounting';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher-ventes';

	$t->mainTitle = new \farm\FarmUi()->getPreAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'reconciliate-sales', ['import' => array_sum($data->counts['import']), 'reconciliate-sales' => $data->counts['reconciliate']['sales'], 'reconciliate-operations' => $data->counts['reconciliate']['operations']]);

	echo '<div class="util-block-help">';
	echo s("Cette page vous permet de rapprocher vos ventes et factures avec les opérations bancaires que vous avez importées.");
	echo '</div>';

	if($data->ccSuggestion->empty()) {

		$linkPreAccounting = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite?';
		if($data->eFinancialYear->notEmpty()) {
			$linkPreAccounting .= 'from='.$data->eFinancialYear['startDate'].'&to='.$data->eFinancialYear['endDate'];
		}

		echo '<div class="util-info">';
		echo '<p>'.s("Il n'y a aucune vente à rapprocher.").'</p>';
		echo '<p>'.s("Souhaitez-vous <linkPreAccounting>préparer vos données de vente</linkPreAccounting> ou <linkCashflow>réaliser un import bancaire</linkCashflow> ?", [
				'linkPreAccounting' => '<a href="'.$linkPreAccounting.'">',
				'linkCashflow' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">',
			]).'</p>';
		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->tableByCashflow($data->eFarm, $data->ccSuggestion);

	}

});

new AdaptativeView('/precomptabilite:rapprocher-ecritures', function($data, FarmTemplate $t) {

	$t->nav = 'preaccounting';

	$t->title = s("Rapprocher les écritures comptables de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher-ecritures';

	$t->mainTitle = new \farm\FarmUi()->getPreAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'reconciliate-operations', ['import' => array_sum($data->counts['import']), 'reconciliate-sales' => $data->counts['reconciliate']['sales'], 'reconciliate-operations' => $data->counts['reconciliate']['operations']]);

	echo '<div class="util-block-help">';
	echo s("Cette page vous permet de rapprocher vos écritures comptables avec les opérations bancaires que vous avez importées.");
	echo '</div>';

	if($data->ccSuggestion->empty()) {

		echo '<div class="util-info">';
		echo '<p>'.s("Il n'y a aucune écriture à rapprocher.").'</p>';
		echo '<p>'.s("Souhaitez-vous <linkOperation>ajouter une écriture dans le journal</linkOperation> ou <linkCashflow>réaliser un import bancaire</linkCashflow> ?", [
				'linkOperation' => '<a href="'.\company\CompanyUi::urlJournal($data->eFarm).'">',
				'linkCashflow' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">',
			]).'</p>';
		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->tableByOperations($data->eFarm, $data->ccSuggestion);

	}

});

