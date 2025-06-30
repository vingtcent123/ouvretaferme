<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'bank';
	$t->subNav = 'import';

	$t->title = s("Les imports bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlBank($data->eFarm).'/import';

	$t->mainTitle = new \bank\ImportUi()->getImportTitle($data->eFarm, $data->eFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlBank($data->eFarm).'/import?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \bank\ImportUi()->getImport($data->eFarm, $data->cImport, $data->imports, $data->eFinancialYear);

});

new AdaptativeView('import', function($data, PanelTemplate $t) {

	return new \bank\CashflowUi()->import($data->eFarm);

});
