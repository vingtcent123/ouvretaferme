<?php
new AdaptativeView('/facturation-electronique/ventes/', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';
	$t->subNav = 'sell';

	$t->title = s("Les factures de vente de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/facturation-electronique/ventes/';

	$t->mainTitle = '<h1>'.s("Les factures de vente").'</h1>';

	// Envisager un util-summarize util-summarize-overflow"

	if($data->cInvoice->empty()) {

		echo '<div class="util-empty">'.s("Il n'y a pas encore de factures de vente à afficher").'</div>';

	} else {

		echo new \invoicing\InvoiceUi()->list($data->eFarm, $data->cInvoice, \invoicing\Invoice::OUT);

	}

});

