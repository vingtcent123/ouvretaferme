<?php
new AdaptativeView('/ventes/rapprocher', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/ventes/rapprocher';

	$t->mainTitle = new \farm\FarmUi()->getAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'reconciliate', ['import' => array_sum($data->numberImport), 'reconciliate' => $data->numberReconciliate]);

	echo '<div class="util-block-help">';
		echo s("Cette page vous permet de rapprocher vos ventes et factures, importées en comptabilité, avec les opérations bancaires que vous avez importées.");
	echo '</div>';

	if($data->ccSuggestion->empty()) {

		echo '<div class="util-info">';
			echo '<p>'.s("Il n'y a aucune vente à rapprocher.").'</p>';
			echo '<p>'.s("Souhaitez-vous <linkImport>importer vos ventes</linkImport>, <linkPreAccounting>préparer vos données de vente</linkPreAccounting>, ou <linkCashflow>réaliser un import bancaire</linkCashflow> ?", [
				'linkImport' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/ventes/importer">',
				'linkPreAccounting' => '<a href="'.\farm\FarmUi::urlSellingSalesAccounting($data->eFarm).'?from='.$data->eFinancialYear['startDate'].'&to='.$data->eFinancialYear['endDate'].'">',
				'linkCashflow' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/imports">',
				]).'</p>';
		echo '</div>';

	} else {

		echo new \invoicing\ReconciliateUi()->table($data->eFarm, $data->ccSuggestion);

	}

});

