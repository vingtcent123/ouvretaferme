<?php
new AdaptativeView('/facturation-electronique/facture/{id}', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	if($data->e['direction'] === \invoicing\Invoice::OUT) {

		$t->subNav = 'sell';

	} else {

		$t->subNav = 'buy';

	}

	$t->title = s("Une facture électronique de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/facturation-electronique/facture/'.$data->e['id'];

	$t->mainTitle = '<h1><a onclick="history.back();" class="h-back">'.\Asset::icon('arrow-left').'</a> '.s("Facture {value}", $data->e['number']).'</h1>';

	echo new \invoicing\InvoiceUi()->summary($data->e);

	echo new \invoicing\LineUi()->list($data->e['cLine']);

	echo new \invoicing\EventUi()->list($data->e['cEvent']);

});

