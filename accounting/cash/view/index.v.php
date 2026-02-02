<?php
new AdaptativeView('/journal-de-caisse', function($data, FarmTemplate $t) {

	$t->title = s("Journal de caisse de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/journal-de-caisse';

	$t->nav = 'cash';

	$t->mainTitle = new \cash\RegisterUi()->getHeader($data->eRegisterCurrent, $data->cRegister);

	if($data->cRegister->empty()) {

		echo '<h3>'.s("Configurer mon journal de caisse").'</h3>';

		echo new \cash\RegisterUi()->create($data->eRegisterCreate, start: TRUE)->body;

	} else {


		if($data->eRegisterCurrent['lines'] > 0) {

			echo new \cash\CashUi()->getChoice($data->eRegisterCurrent);
			echo new \cash\CashUi()->getSearch($data->eRegisterCurrent, $data->search);
			echo new \cash\CashUi()->getList($data->cCash, $data->search, $data->page);

		} else {

			echo new \cash\CashUi()->start($data->eRegisterCurrent);

		}

	}

});

