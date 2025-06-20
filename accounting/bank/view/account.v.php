<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les comptes bancaires de {farm}", ['farm' => $data->eFarm['name']]);
	$t->tab = 'settings';
	$t->canonical = \company\CompanyUi::urlBank($data->eFarm).'/account/';

	$t->mainTitle = new \bank\BankAccountUi()->getAccountTitle($data->eFinancialYear);

	echo new \bank\BankAccountUi()->list($data->eFarm, $data->cBankAccount);

});


?>
