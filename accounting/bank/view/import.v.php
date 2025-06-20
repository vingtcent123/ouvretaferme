<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les imports bancaires de {company}", ['company' => $data->eFarm['name']]);
	$t->tab = 'bank';
	$t->subNav = new \company\CompanyUi()->getBankSubNav($data->eFarm);
	$t->canonical = \company\CompanyUi::urlBank($data->eFarm).'/import';

	$t->js()->replaceHistory($t->canonical);
	$t->package('main')->updateHeader(
		$this->tab,
		'import',
		$this->getCompanyNav(),
		$this->getCompanySubNav(),
	);

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
