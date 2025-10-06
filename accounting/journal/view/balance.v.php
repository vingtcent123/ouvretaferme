<?php

new AdaptativeView(
	'index', function ($data, FarmTemplate $t) {

	$t->nav = 'journal';
	$t->subNav = 'balance';

	$t->title = s("La balance comptable de {farm}", ['farm' => $data->eFarm['name']]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/balance';

	$t->mainTitle = new \journal\BalanceUi()->getTitle();

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function (\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eFarm).'/balance?financialYear='.$eFinancialYear['id'].'&'.http_build_query($data->search->getFiltered(['financialYear']));
		},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	echo new \journal\BalanceUi()->getSearch($data->search, $data->eFinancialYear);
	echo new \journal\BalanceUi()->display($data->eFinancialYear, $data->eFinancialYearPrevious, $data->trialBalanceData, $data->trialBalancePreviousData, $data->search);

}
);
