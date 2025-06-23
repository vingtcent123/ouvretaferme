<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'journal';
	$t->subNav = 'accounts';

	$t->title = s("Les comptes de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/accounts';

	$t->mainTitle = new \journal\AccountsUi()->getAccountsTitle($data->eFinancialYear);
	$t->mainTitleClass = 'hide-lateral-down';

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eFarm).'/accounts?financialYear='.$eFinancialYear['id'].'&'.http_build_query($data->search->getFiltered(['financialYear']));
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	if($data->eFinancialYear->notEmpty()) {
		echo new \journal\AccountsUi()->getSearch($data->search, $data->eFinancialYear, $data->eThirdParty);
	}
	echo new \journal\AccountsUi()->getJournal($data->eFarm, $data->cOperation, $data->eFinancialYear, $data->search);

});
