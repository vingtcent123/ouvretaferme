<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'journal';
	$t->subNav = 'operations';

	$t->title = s("Le journal comptable de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/operations';

	$t->mainTitle = new \journal\JournalUi()->getJournalTitle($data->eFarm, $data->eFinancialYear);

	$t->mainYear = new \account\FinancialYearUi()->getFinancialYearTabs(
		function(\account\FinancialYear $eFinancialYear) use ($data) {
			return \company\CompanyUi::urlJournal($data->eFarm).'/operations?financialYear='.$eFinancialYear['id'].'&'.http_build_query($data->search->getFiltered(['financialYear']));
			},
		$data->cFinancialYear,
		$data->eFinancialYear,
	);

	if($data->eFinancialYear->notEmpty()) {
		echo new \journal\JournalUi()->getSearch($data->eFarm, $data->search, $data->eFinancialYear, $data->eCashflow, $data->eThirdParty, $data->cPaymentMethod);
	}

	$selectedJournalCode = GET('code');
	if(
		in_array($selectedJournalCode, \journal\Operation::model()->getPropertyEnum('journalCode')) === FALSE and
		in_array($selectedJournalCode, ['vat-buy', 'vat-sell']) === FALSE
	) {
		$selectedJournalCode = NULL;
	}

	echo '<div class="tabs-h" id="journals">';
		echo new \journal\JournalUi()->getJournalTabs($data->eFarm, $data->eFinancialYear, $selectedJournalCode);
		echo new \journal\JournalUi()->getJournal($data->eFarm, $data->cOperation, $data->eFinancialYear, selectedJournalCode: $selectedJournalCode, operationsVat: $data->operationsVat, search: $data->search, cPaymentMethod: $data->cPaymentMethod);
	echo '</div>';

});
