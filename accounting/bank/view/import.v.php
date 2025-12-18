<?php
new AdaptativeView('/banque/imports', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Les imports bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/banque/imports';

	$t->mainTitle = new \farm\FarmUi()->getAccountingBankTitle($data->eFarm, 'import', $data->nSuggestion, NULL);

	echo new \bank\ImportUi()->getImport($data->eFarm, $data->cImport, $data->imports, $data->eFinancialYear);

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm));

});

new AdaptativeView('/banque/imports:import', function($data, PanelTemplate $t) {

	return new \bank\CashflowUi()->import($data->eFarm);

});
