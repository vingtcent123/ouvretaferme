<?php
new AdaptativeView('index', function($data, FarmTemplate $t) {

	$t->nav = 'journal';
	$t->subNav = 'operations';

	$t->title = s("Le livre journal de {farm}", ['farm' => encode($data->eFarm['name'])]);
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

		echo new \journal\JournalUi()->getJournalTabs($data->eFarm, $data->eFinancialYear, $data->search, $selectedJournalCode);

		switch($selectedJournalCode) {

			case NULL:
				echo '<div class="tab-panel selected" data-tab="journal">';
				echo new \journal\JournalUi()->getTableContainer($data->eFarm, NULL, $data->cOperation, $data->eFinancialYear, $data->search);
				echo '</div>';
				break;

			case 'vat-buy':
				echo '<div class="tab-panel selected" data-tab="journal-'.$selectedJournalCode.'">';
				echo new \journal\VatUi()->getTableContainer($data->eFarm, $data->operationsVat['buy'] ?? new \Collection(), 'buy', $data->search);
				echo '</div>';
				break;

			case  'vat-sell':
				echo '<div class="tab-panel selected" data-tab="journal-'.$selectedJournalCode.'">';
				echo new \journal\VatUi()->getTableContainer($data->eFarm, $data->operationsVat['sell'] ?? new \Collection(), 'buy', $data->search);
				echo '</div>';
				break;

			default:
				echo '<div class="tab-panel selected" data-tab="journal-'.$selectedJournalCode.'">';
				echo new \journal\JournalUi()->getTableContainer($data->eFarm, $selectedJournalCode, $data->cOperation, $data->eFinancialYear, $data->search);
				echo '</div>';
				break;

		}

		echo new \journal\JournalUi()->getBatch($data->eFarm, $data->cPaymentMethod);


	echo '</div>';

});
