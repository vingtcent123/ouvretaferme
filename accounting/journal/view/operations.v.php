<?php
new AdaptativeView('onboarding', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Le livre journal de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/livre-journal';

	$t->mainTitle = new \journal\JournalUi()->getJournalTitle($data->eFarm, FALSE);

	echo '<div class="util-block-help">';
		echo '<h4>'.s("Vous pouvez maintenant créer votre première écriture pour l'exercice {value} !", \account\FinancialYearUi::getYear($data->eFarm['eFinancialYear'])).'</h4>';
		echo '<p>'.s("Vous pouvez enregistrer vos écritures de plusieurs manières sur {siteName} :").'</p>';
		echo '<ul class="journal-onboarding-list">';
			echo '<li>'.s("Depuis vos <link>opérations bancaires</link>, après avoir importé un relevé bancaire", ['link' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations">']).'</li>';
			echo '<li>';
				echo s("En important les <link>factures que vous avez rapprochées</link>.", ['link' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer">']);
				if($data->nInvoice > 0) {
					echo ' '.p("Vous avez d'ailleurs <b>{value}</b> facture à importer.", "Vous avez d'ailleurs <b>{value}</b> factures à importer.", $data->nInvoice);
				}
			echo '</li>';
			echo '<li>'.s("Directement sur cette page, en cliquant sur <link>{icon}Enregistrer une écriture</link>", ['link' => '<a class="btn btn-xs btn-primary" href="'.\company\CompanyUi::urlJournal($data->eFarm).'/operation:create?journalCode">', 'icon' => \Asset::icon('plus-circle').' ']).'</li>';
			echo '<li>'.s("Ou en important un fichier {fec}, si vous utilisiez un autre logiciel de comptabilité, depuis les {icon} <link>Paramètres de l'exercice comptable</link>", ['link' => '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/etats-financiers/">', 'icon' => \Asset::icon('gear')]).'</li>';
		echo '</ul>';
	echo '</div>';

	echo '<br/>';


});

new AdaptativeView('/journal/livre-journal', function($data, FarmTemplate $t) {


	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Le livre journal de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlJournal($data->eFarm).'/livre-journal';

	$t->mainTitle = new \journal\JournalUi()->getJournalTitle($data->eFarm);

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

		if($nGroup > 0) {

			$number = p("Il y a <b>{value}</b> groupe d'écritures déséquilibré.", "Il y a actuellement <b>{value}</b> groupes d'écritures déséquilibrés.", $nGroup);

			echo '<div class="util-block-info">'.s("Vous affichez actuellement tous les groupes d'écritures qui ne sont <b>pas équilibrés</b>.").'<br />'.$number.'</div>';

		}
	}

	echo '<div class="tabs-h" id="journals"';
		if($data->eOperationRequested->notEmpty()) {
			echo ' onrender="Operation.open('.$data->eOperationRequested['id'].');"';
		}
	echo ' data-batch="#batch-journal">';

		echo new \journal\JournalUi()->getJournalTabs($data->eFarm, $data->eFarm['eFinancialYear'], $data->search, $selectedJournalCode, $data->cJournalCode);

		if($data->unbalanced and $nGroup === 0) {

			echo '<div class="util-block-success">'.Asset::icon('stars').' '.s("Toutes vos écritures sont équilibrées !<br />").'</div>';

		} else {

			switch($selectedJournalCode) {

				case NULL:
					echo '<div class="tab-panel selected" data-tab="journal">';
					echo new \journal\JournalUi()->list($data->eFarm, NULL, $data->cOperation, $data->eFarm['eFinancialYear'], $data->search, displayTotal: $data->search->notEmpty(['ids']) and ((int)$data->nPage === 1 or $data->nPage === NULL));
					echo '</div>';
					break;

				case 'vat-buy':
					echo '<div class="tab-panel selected" data-tab="journal-'.$selectedJournalCode.'">';
					echo new \journal\VatUi()->getTableContainer($data->eFarm, $data->operationsVat['buy'] ?? new \Collection(), $data->search);
					echo '</div>';
					break;

				case  'vat-sell':
					echo '<div class="tab-panel selected" data-tab="journal-'.$selectedJournalCode.'">';
					echo new \journal\VatUi()->getTableContainer($data->eFarm, $data->operationsVat['sell'] ?? new \Collection(), $data->search);
					echo '</div>';
					break;

				default:
					echo '<div class="tab-panel selected" data-tab="journal-'.$selectedJournalCode.'">';
					echo new \journal\JournalUi()->list($data->eFarm, $selectedJournalCode, $data->cOperation, $data->eFarm['eFinancialYear'], $data->search);
					echo '</div>';
					break;

			}
		}

		echo new \journal\JournalUi()->getBatch($data->eFarm, $data->cPaymentMethod, $data->cJournalCode);

	echo '</div>';

	if(isset($data->nPage)) {
		echo \util\TextUi::pagination($data->page, $data->nPage);
	}

});

new AdaptativeView('attach', function($data, PanelTemplate $t) {

	return new \journal\OperationUi()->getAttach($data->eFarm, $data->cOperation, $data->cCashflow, $data->tip);

});
