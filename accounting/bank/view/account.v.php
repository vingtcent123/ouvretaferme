<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les comptes bancaires de {company}", ['company' => $data->eCompany['name']]);
	$t->tab = 'settings';
	$t->canonical = \company\CompanyUi::urlBank($data->eCompany).'/account/';

	$t->mainTitle = new \bank\AccountUi()->getAccountTitle($data->eFinancialYear);

	echo new \bank\AccountUi()->list($data->eCompany, $data->cAccount);

});


?>
