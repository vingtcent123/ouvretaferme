<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Le journal comptable de {farm}", ['farm' => $data->eFarm['name']]);
	$t->tab = 'journal';
	$t->subNav = new \journal\JournalUi()->getJournalSubNav($data->eFarm);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm);

	$t->mainTitle = new \journal\JournalUi()->getJournalTitle($data->eFarm, $data->eFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eFarm).'/?financialYear='.$eFinancialYear['id'].'&'.http_build_query($data->search->getFiltered(['financialYear']));
			},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	if($data->eFinancialYear->notEmpty()) {
		echo new \journal\JournalUi()->getSearch($data->search, $data->eFinancialYear, $data->eCashflow, $data->eThirdParty);
	}
	echo new \journal\JournalUi()->getJournal($data->eFarm, $data->cOperation, $data->eFinancialYear, $data->search);

});
