<?php
new AdaptativeView('/journal/livre-journal', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Le livre journal de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/livre-journal';

	$t->mainTitle = new \journal\JournalUi()->getJournalTitle($data->eFarm, $data->counts);

	if($data->eFarm['eFinancialYear']->notEmpty()) {
		echo new \journal\JournalUi()->getSearch(
			eFarm: $data->eFarm,
			search: $data->search,
			eFinancialYearSelected: $data->eFarm['eFinancialYear'],
			eCashflow: $data->eCashflow,
			eThirdParty: $data->eThirdParty,
			cPaymentMethod: $data->cPaymentMethod,
			nUnbalanced: $data->nUnbalanced,
		);
	}

	$selectedJournalCode = GET('journalCode');
	if(
		in_array($selectedJournalCode, $data->cJournalCode->getIds()) === FALSE and
		in_array($selectedJournalCode, ['vat-buy', 'vat-sell', \journal\JournalSetting::JOURNAL_CODE_BANK]) === FALSE
	) {
		$selectedJournalCode = NULL;
	}

	if(GET('financialYear') === '0') {
		echo '<div class="util-block-help">'.s("Vous visualisez actuellement les écritures comptables <b>tous exercices confondus</b>. Certaines actions ne sont donc <b>pas</b> disponibles.").'</div>';
	}

	if($data->search->get('needsAsset') === 1 and $data->cOperation->notEmpty()) {
		echo '<div class="util-block-help">';
			echo p("Une écriture d'immobilisation n'a pas encore de fiche d'immobilisation. Créez-la dès à présent !", "Les écritures d'immobilisations qui n'ont pas encore de fiche d'immobilisation sont listées ci-après. Créez leurs fiches d'immobilisation dès à présent !", $data->cOperation->count());
		echo '</div>';

	}

	if($data->unbalanced === TRUE) {

		$nGroup = count(array_unique($data->cOperation->getColumn('hash')));
		$number = p("Il y a <b>{value}</b> groupe d'écritures déséquilibré.", "Il y a actuellement <b>{value}</b> groupes d'écritures déséquilibrés.", $nGroup);

		echo '<div class="util-block-important">'.s("Vous affichez actuellement tous les groupes d'écritures qui ne sont <b>pas équilibrés</b>.").'<br />'.$number.'</div>';
	}

	echo '<div class="tabs-h" id="journals"';
		if($data->eOperationRequested->notEmpty()) {
			echo ' onrender="Operation.open('.$data->eOperationRequested['id'].');"';
		}
	echo ' data-batch="#batch-journal">';

		echo new \journal\JournalUi()->getJournalTabs($data->eFarm, $data->eFarm['eFinancialYear'], $data->search, $selectedJournalCode, $data->cJournalCode);

		switch($selectedJournalCode) {

			case NULL:
				echo '<div class="tab-panel selected" data-tab="journal">';
				echo new \journal\JournalUi()->list($data->eFarm, NULL, $data->cOperation, $data->eFarm['eFinancialYear'], $data->search);
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
				echo new \journal\JournalUi()->list($data->eFarm, $selectedJournalCode, $data->cOperation, $data->eFarm['eFinancialYear'], $data->search);
				echo '</div>';
				break;

		}

		echo new \journal\JournalUi()->getBatch($data->eFarm, $data->cPaymentMethod, $data->cJournalCode);

	echo '</div>';

	if(isset($data->nPage)) {
		echo \util\TextUi::pagination($data->page, $data->nPage);
	}

});

new AdaptativeView('attach', function($data, PanelTemplate $t) {

	return new \journal\OperationUi()->getAttach($data->eFarm, $data->cOperation, $data->tip);

});
