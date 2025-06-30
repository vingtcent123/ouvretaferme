<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'journal';
	$t->subNav = 'vat';

	$t->title = s("Les journaux de TVA de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/vat';

	$t->mainTitle = new \journal\VatUi()->getTitle();

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eFarm).'/vat?financialYear='.$eFinancialYear['id'].'&'.http_build_query($data->search->getFiltered(['financialYear']));
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \journal\VatUi()->getSearch($data->search, $data->eFinancialYear, $data->eThirdParty);
	echo new \journal\VatUi()->getJournal($data->eFarm, $data->eFinancialYear, $data->operations, $data->search);

});
