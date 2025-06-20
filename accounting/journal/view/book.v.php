<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Le Grand Livre de {farm}", ['eFarm' => $data->eFarm['name']]);
	$t->tab = 'journal';
	$t->subNav = new \journal\JournalUi()->getJournalSubNav($data->eFarm);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/book';

	$t->mainTitle = new \journal\BookUi()->getBookTitle($data->eFarm);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eFarm).'/book?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \journal\BookUi()->getBook($data->eFarm, $data->cOperation, $data->eFinancialYear);

});
