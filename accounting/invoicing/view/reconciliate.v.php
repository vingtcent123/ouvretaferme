<?php
new AdaptativeView('/ventes/rapprocher', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Les ventes de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/ventes/rapprocher';

	$t->mainTitle = new \farm\FarmUi()->getAccountingInvoiceTitle($data->eFarm, $data->eFinancialYear, 'reconciliate', ['import' => array_sum($data->numberImport), 'reconciliate' => $data->numberReconciliate]);

	echo '<div class="util-block-help">';
	echo s("Cette page vous permet de rapprocher vos ventes et facture, importées en comptabilité, avec les opérations bancaires que vous avez importées.");
	echo '</div>';


});

