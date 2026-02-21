<?php
new AdaptativeView('/facturation-electronique', function($data, FarmTemplate $t) {

	$t->nav = 'invoicing';

	$t->title = s("Les factures de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/facturation-electronique';

	$this->mainTitleClass = 'invoicing-presentation';

	$t->mainTitle = '<h1>'.s("Facturation électronique").'</h1>';

	echo '<h2>En cours...</h2>';


});

