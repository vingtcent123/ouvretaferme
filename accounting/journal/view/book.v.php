<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'journal';
	$t->subNav = 'book';

	$t->title = s("Le Grand Livre de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/book';

	$t->mainTitle = new \journal\BookUi()->getBookTitle($data->eFarm);
	$t->mainTitleClass = 'hide-lateral-down';

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eFarm).'/book?financialYear='.$eFinancialYear['id'];
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \journal\BookUi()->getBook($data->eFarm, $data->cOperation, $data->eFinancialYear);

});
