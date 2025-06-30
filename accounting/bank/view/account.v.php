<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Les comptes bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlBank($data->eFarm).'/account/';

	$t->mainTitle = new \bank\BankAccountUi()->getAccountTitle($data->eFarm);

	echo new \bank\BankAccountUi()->list($data->eFarm, $data->cBankAccount);

});


?>
