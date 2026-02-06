<?php
new AdaptativeView('/banque/imports', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Les imports bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlConnected($data->eFarm).'/banque/imports';

	$t->mainTitle = new \farm\FarmUi()->getAccountingBankTitle($data->eFarm, 'import', NULL);

	if($data->cImportLonely->notEmpty()) {

		echo '<a class="btn btn-secondary" href="'.\company\CompanyUi::urlBank($data->eFarm).'/import:update?id='.$data->cImportLonely->first()['id'].'">';
			echo Asset::icon('gear').' ';
			echo s("L'import n°{value} a besoin d'une dernière configuration", $data->cImportLonely->first()['id']);
		echo '</a>';

	}

	if($data->cBankAccount->count() > 1) {
		echo new \bank\ImportUi()->getTabs($data->eFarm, $data->cBankAccount);
	}

	echo new \bank\ImportUi()->getImport($data->eFarm, $data->cImport, $data->imports);

});

new AdaptativeView('/banque/imports:import', function($data, PanelTemplate $t) {

	return new \bank\CashflowUi()->import($data->eFarm);

});


new AdaptativeView('update', function($data, PanelTemplate $t) {

	return new \bank\ImportUi()->update($data->eFarm, $data->eImport, $data->cBankAccount);

});
