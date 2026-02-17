<?php
new AdaptativeView('/precomptabilite', function($data, FarmTemplate $t) {

	$t->title = s("Précomptabilité de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlFinancialYear(NULL, $data->eFarm).'/precomptabilite';

	$t->nav = 'accounting';
	$t->subNav = 'preaccounting';

	$t->mainTitle = '<h1>'.Asset::icon('magic').' '.s("Importer des opérations dans le livre journal").'</h1>';

	if($data->eFarm['hasSales'] === FALSE and $data->cCash->empty()) {

		echo '<div class="util-block-help">'.
			'<h3>'.s("Vous êtes sur la page pour importer automatiquement en comptabilité").'</h3>'.
			'<p>'.s("Les paiements de vos factures ainsi que les opérations de vos journaux de caisse peuvent être importés en un clic dans votre comptabilité après avoir préparé les données de vos ventes. À ce jour, vous n'avez pas encore utilisé le module de vente, la précomptabilité n'est donc pas disponible.").'</p>'.
			'<a href="'.\farm\FarmUi::urlSellingSales($data->eFarm).'" class="btn btn-secondary">'.s("Créer ma première vente").'</a>';

			if($data->cRegister->empty()) {
				echo ' <a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/journal-de-caisse" class="btn btn-secondary">'.s("Créer un journal de caisse").'</a>';
			} else if($data->cRegister->count() === 1) {
				echo ' <a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/journal-de-caisse" class="btn btn-secondary">'.s("Voir mon journal de caisse").'</a>';
			} else {
				echo ' <a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/journal-de-caisse" class="btn btn-secondary">'.s("Voir mes journaux de caisse").'</a>';
			}

		echo '</div>';

		return;
	}

	if($data->cInvoice->empty() and $data->cCash->empty() and $data->cInvoiceImported->empty() and $data->cCashImported->empty()) {

		echo '<div class="util-block-help">'.
			'<h3>'.s("Vous êtes sur la page pour importer automatiquement en comptabilité").'</h3>'.
			'<p>'.s("Les paiements de vos factures ainsi que les opérations de vos journaux de caisse peuvent être importés en un clic dans votre comptabilité après avoir préparé les données de vos ventes.").'</p>'.
			'<p>'.s("Il n'y a aucune facture ni opération de journal de caisse éligible à l'import en comptabilité pour l'<b>exercice {year}</b>.", ['year' => \account\FinancialYearUi::getYear($data->eFarm['eFinancialYear'])]).'</p>'.
			'<a href="'.\farm\FarmUi::urlSellingInvoices($data->eFarm).'" class="btn btn-secondary">'.s("Voir mes factures").'</a>'.
		'</div>';

		return;

	}

	echo new \preaccounting\PreaccountingUi()->check($data->eFarm, $data->dates, $data->cInvoice, $data->cInvoiceImported, $data->cRegister, $data->cCash, $data->cCashImported);

});

new AdaptativeView('/precomptabilite/verifier', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité de {value}", $data->eFarm['name']);
	$t->canonical = \farm\FarmUi::urlFinancialYear(NULL, $data->eFarm).'/precomptabilite/verifier:'.($data->checkType);

	if($data->checkType === 'import') {

		$t->nav = 'accounting';
		$t->subNav = 'preaccounting';

	} else {

		$t->nav = 'preaccounting';

	}

	if($data->checkType === 'fec') {

		$mainTitle = ("Précomptabilité");

	} else {

		$month = \util\DateUi::getMonthName(mb_substr($data->search->get('from'), 5, 2));
		$year = mb_substr($data->search->get('from'), 0, 4);

		$mainTitle = '<a href="'.\farm\FarmUi::urlFinancialYear(NULL, $data->eFarm).'/precomptabilite"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
		if($data->search->get('from') < date('Y-m-01')) {
			$mainTitle .= s("Importer les opérations de {month} {year}", ['month' => $month, 'year' => $year]);
		} else {
			$mainTitle .= s("Consulter les opérations de {month} {year}", ['month' => $month, 'year' => $year]);
		}

	}

	$t->mainTitle = '<h1>'.$mainTitle.'</h1>';

	if($data->eFarm['hasSales'] === FALSE and $data->cCash->empty()) {

		echo '<div class="util-block-help">';
			echo '<h3>'.s("Vous êtes sur la page de précomptabilité").'</h3>';
			echo '<p>'.s("La précomptabilité est l'opération préparatoire de vos ventes avant l'intégration dans votre comptabilité. Après avoir associé des numéros de compte à vos produits, vous pourrez exporter un {fec}.", ['fec' => '<span class="util-badge bg-primary">FEC</span>']).'</p>';
			echo '<p>'.s("À ce jour, vous n'avez pas encore utilisé le module de vente, la précomptabilité n'est donc pas disponible.").'</p>';
			echo '<a href="'.\farm\FarmUi::urlSellingSales($data->eFarm).'" class="btn btn-secondary">'.s("Créer une première vente").'</a>';
		echo '</div>';

		return;

	}

	if($data->checkType === 'fec') {

		echo new \preaccounting\PreaccountingUi()->getSearchPeriod($data->search);

	} else if($data->search->get('from') >= date('Y-m-01')) {

		echo '<div class="util-block-info">';
			echo s("Les opérations sont consultables en lecture seulement car le mois de {month} {year} n'est pas encore terminé.<br />Vous pouvez d'ores et déjà préparer les données !", ['month' => $month, 'year' => $year]);
		echo '</div>';

	} else if($data->eFarm['eFinancialYear']['status'] === \account\FinancialYear::CLOSE) {

		echo '<div class="util-block-info">';
		echo '<h4>'.s("Attention").'</h4>';
			echo s("Les opérations sont consultables en lecture seulement car l'exercice {year} est clos.", ['year' => \account\FinancialYearUi::getYear($data->eFarm['eFinancialYear'])]);
		echo '</div>';
	}

	echo new \preaccounting\PreaccountingUi()->getCheckSteps($data->nProductToCheck,  $data->nItemToCheck, $data->nInvoiceForPaymentToCheck,  $data->cRegisterMissing, $data->type, $data->checkType, $t->canonical, $data->search);

	switch($data->type) {

		case 'product':
			echo '<div data-step="'.$data->type.'" class="stick-md util-overflow-md">';
				echo new \preaccounting\PreaccountingUi()->products(
					$data->eFarm,
					$data->cProduct,
					$data->cCategories,
					$data->products,
					$data->search,
					$data->nProductToCheck,
					itemData: ['nToCheck' => $data->nItemToCheck, 'cItem' => $data->cItem],
				);
			echo '</div>';
			break;

		case 'payment':
			echo '<div data-step="'.$data->type.'" class="stick-md util-overflow-md">';
				if($data->checkType === 'fec') {
					echo new \preaccounting\PreaccountingUi()->invoices($data->eFarm, $data->cInvoiceForPayment, $data->cPaymentMethod, $data->search);
				} else {
					echo new \preaccounting\PreaccountingUi()->registers($data->eFarm, $data->cRegister, $data->search);
				}
			echo '</div>';
			break;

		case 'export':
			if($data->checkType === 'fec') {

				echo new \preaccounting\PreaccountingUi()->export($data->eFarm, $data->operations, $data->nSale, $data->nInvoice, $data->nCash, $data->cInvoice, $data->search);

			} else {

				if(empty($data->lastValidationDate) === FALSE) {

					echo '<div class="util-block bg-primary color-white">';
						echo \Asset::icon('lock-fill').'  '.s("Votre livre-journal est actuellement validé jusqu'au {closed}, la saisie de nouvelles écritures est possible à partir du {open}.", [
							'closed' => \util\DateUi::numeric($data->lastValidationDate),
							'open' => \util\DateUi::numeric(date('Y-m-d', strtotime($data->lastValidationDate.' + 1 DAY'))),
						]);
					echo '</div>';
				}

				echo new \preaccounting\ImportUi()->list(
					$data->eFarm,
					$data->cOperation,
					$data->lastValidationDate,
					$data->search
				);
			}
			break;
	}

});

new AdaptativeView('/precomptabilite:rapprocher', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Rapprocher les opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \farm\FarmUi::urlFinancialYear(NULL, $data->eFarm).'/precomptabilite:rapprocher';
	$navigation = '<a href="'.\farm\FarmUi::urlConnected($data->eFarm).'/banque/operations" class="h-back">'.\Asset::icon('arrow-left').'</a>';
	$t->mainTitle = '<h1>'.$navigation.s("Rapprocher les opérations bancaires").($data->nSuggestionWaiting > 0 ? '<span class="util-counter ml-1">'.$data->nSuggestionWaiting.'</span>' : '').'</h1>';

	if($data->ccSuggestion->empty()) {

		echo '<div class="util-empty">'.s("Il n'y a aucune opération bancaire à rapprocher pour le moment !").'</div>';

		echo '<div class="util-block-info">';
			echo \Asset::icon('fire', ['class' => 'util-block-icon']);

			echo '<p>';

			if($data->eImportLast->empty()) {

				echo s("Vous n'avez pas encore réalisé d'import bancaire.");

			} else {

				echo s("Votre dernier import bancaire a permis de faire des rapprochements jusqu'au {date}.", ['date' => \util\DateUi::numeric($data->eImportLast['endDate'], \util\DateUi::DATE)]);

			}

			echo '</p>';

			if($data->eImportLast->empty()) {
				echo '<a class="btn btn-transparent" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/banque/imports:import">'.s("Importer un premier relevé bancaire").'</a>';
			} else {
				echo '<a class="btn btn-transparent" href="'.\farm\FarmUi::urlConnected($data->eFarm).'/banque/imports:import">'.s("Importer un relevé bancaire").'</a>';
			}

		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->list($data->eFarm, $data->ccSuggestion, $data->cMethod);

	}

});

