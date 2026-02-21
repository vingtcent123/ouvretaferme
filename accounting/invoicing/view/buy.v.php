<?php
new AdaptativeView('/facturation-electronique/achats/', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';
	$t->subNav = 'buy';

	$t->title = s("Les factures d'achat de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/facturation-electronique/buy/';

	$t->mainTitle = '<h1>'.s("Les factures d'achat").'</h1>';

	// Envisager un util-summarize util-summarize-overflow"

	if($data->cInvoice->empty()) {

		echo '<div class="util-empty">'.s("Il n'y a pas encore de factures d'achat à afficher").'</div>';

	} else {

		echo new \invoicing\InvoiceUi()->list($data->eFarm, $data->cInvoice, \invoicing\Invoice::IN);

	}

});

