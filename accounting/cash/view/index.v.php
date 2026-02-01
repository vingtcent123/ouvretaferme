<?php
new AdaptativeView('/cahier-de-caisse', function($data, FarmTemplate $t) {

	$t->title = s("Cahier de caisse de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/cahier-de-caisse';

	$t->nav = 'cash';

	$t->mainTitle = new \cash\RegisterUi()->getHeader($data->eRegisterCurrent, $data->cRegister);

	if($data->cRegister->empty()) {

		echo '<h3>'.s("Configurer mon cahier de caisse").'</h3>';

		echo new \cash\RegisterUi()->create($data->eRegisterCreate, start: TRUE)->body;

	}

});

