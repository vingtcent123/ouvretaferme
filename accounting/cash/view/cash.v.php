<?php
new AdaptativeView('/cahier-de-caisse', function($data, FarmTemplate $t) {

	$t->title = s("Cahier de caisse de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/cahier-de-caisse';

	$t->nav = 'cash';

	$t->mainTitle = '<h1>'.S("Cahier de caisse").'</h1>';

	if($data->cRegister->empty()) {

		echo '<h3>'.s("Configurer mon cahier de caisse").'</h3>';
/*
		$eCustomer = new \selling\Customer([
			'farm' => $data->eFarm,
			'user' => new \user\User(),
			'nGroup' => $data->cCustomerGroup->count()
		]);

		echo new \selling\CustomerUi()->create($eCustomer)->body;
*/
	}

});

