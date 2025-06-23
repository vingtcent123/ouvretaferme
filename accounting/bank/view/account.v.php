<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'settings-accounting';

	$t->title = s("Les comptes bancaires de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlBank($data->eFarm).'/account/';
	$t->subNav = new \company\CompanyUi()->getSettingsSubNav($data->eFarm);

	$t->mainTitle = new \bank\BankAccountUi()->getAccountTitle($data->eFarm);
	$t->mainTitleClass = 'hide-lateral-down';

	echo new \bank\BankAccountUi()->list($data->eFarm, $data->cBankAccount);

});


?>
