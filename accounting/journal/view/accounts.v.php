<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Les comptes clients et fournisseurs de {company}", ['company' => $data->eCompany['name']]);
	$t->tab = 'journal';
	$t->subNav = new \company\CompanyUi()->getJournalSubNav($data->eCompany);
	$t->canonical = \company\CompanyUi::urlJournal($data->eCompany);

	$t->mainTitle = new \journal\AccountsUi()->getAccountsTitle($data->eFinancialYear);

	$t->mainYear = new \accounting\FinancialYearUi()->getFinancialYearTabs(
		function(\accounting\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eCompany).'/accounts?financialYear='.$eFinancialYear['id'].'&'.http_build_query($data->search->getFiltered(['financialYear']));
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	if($data->eFinancialYear->notEmpty()) {
		echo new \journal\AccountsUi()->getSearch($data->search, $data->eFinancialYear, $data->eThirdParty);
	}
	echo new \journal\AccountsUi()->getJournal($data->eCompany, $data->cOperation, $data->eFinancialYear, $data->search);

});
