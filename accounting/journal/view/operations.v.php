<?php
new AdaptativeView('/journal/livre-journal', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Le livre journal de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/livre-journal';

	$t->mainTitle = new \journal\JournalUi()->getJournalTitle($data->eFarm, $data->eFinancialYear);

	if($data->eFinancialYear->notEmpty()) {
		echo new \journal\JournalUi()->getSearch(
			eFarm: $data->eFarm,
			search: $data->search,
			eFinancialYearSelected: $data->eFinancialYear,
			eCashflow: $data->eCashflow,
			eThirdParty: $data->eThirdParty,
			cPaymentMethod: $data->cPaymentMethod,
			cJournalCode: $data->cJournalCode,
		);
	}

	$selectedJournalCode = GET('journalCode');
	if(
		in_array($selectedJournalCode, $data->cJournalCode->getIds()) === FALSE and
		in_array($selectedJournalCode, ['vat-buy', 'vat-sell', \journal\JournalSetting::JOURNAL_CODE_BANK]) === FALSE
	) {
		$selectedJournalCode = NULL;
	}

	echo '<div class="tabs-h" id="journals"';
		if($data->eOperationRequested->notEmpty()) {
			echo ' onrender="Operation.open('.$data->eOperationRequested['id'].');"';
		}
	echo ' data-batch="#batch-journal">';

		echo new \journal\JournalUi()->getJournalTabs($data->eFarm, $data->eFinancialYear, $data->search, $selectedJournalCode, $data->cJournalCode);

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

		echo new \journal\JournalUi()->getBatch($data->eFarm, $data->cPaymentMethod, $data->cJournalCode);

	echo '</div>';

	$t->package('main')->updateNavAccountingYears(new \farm\FarmUi()->getAccountingYears($data->eFarm, $data->eFinancialYear['id']));

});
