<?php
new AdaptativeView('index', function($data, CompanyTemplate $t) {

	$t->title = s("Le Grand Livre de {company}", ['company' => $data->eCompany['name']]);
	$t->tab = 'journal';
	$t->subNav = new \company\CompanyUi()->getJournalSubNav($data->eCompany);
	$t->canonical = \company\CompanyUi::urlJournal($data->eCompany).'/book';

	$t->mainTitle = new \journal\BookUi()->getBookTitle($data->eCompany);

	$t->mainYear = new \accounting\FinancialYearUi()->getFinancialYearTabs(
		function(\accounting\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eCompany).'/book?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \journal\BookUi()->getBook($data->eCompany, $data->cOperation, $data->eFinancialYear);

});
