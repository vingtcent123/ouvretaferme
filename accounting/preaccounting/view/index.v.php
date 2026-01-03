<?php
new AdaptativeView('/precomptabilite', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité des factures de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite';

	$t->nav = 'preaccounting';

	$toCheck = $data->nProductToCheck + $data->nItemToCheck + $data->nPaymentToCheck;

	if($data->eFarm['hasFinancialYears']) {
		$mainTitle = new \farm\FarmUi()->getAccountingYears($data->eFarm);
	} else {
		$mainTitle = '';
	}

	$mainTitle .= '<div class="util-action">';
		$mainTitle .= '<h1>';
			$mainTitle .= s("Précomptabilité");
			if($toCheck > 0) {
				$mainTitle .= '<span class="util-counter ml-1">'.$toCheck.'</span>';
			}
		$mainTitle .= '</h1>';

		$mainTitle .= '<div>';
			$mainTitle .= '<a href="/doc/accounting" class="btn btn-xs btn-outline-primary">'.\Asset::icon('person-raised-hand').' '.s("Aide").'</a>';
		$mainTitle .= '</div>';

	$mainTitle .= '</div>';
	$t->mainTitle = $mainTitle;


	echo new \preaccounting\PreaccountingUi()->getSearch($data->eFarm, $data->search, 'invoices');

	if(($toCheck + $data->nProductVerified + $data->nPaymentVerified) === 0) {

		echo '<div class="util-block-important">';
			echo s("Il n'a aucune facture à afficher. Avez-vous choisi la bonne période ?");
		echo '</div>';
		return;
	}

	Asset::css('preaccounting', 'step.css');

	$steps = [
		[
			'position' => 1,
			'number' => $data->nProductToCheck + $data->nItemToCheck,
			'numberVerified' => $data->nProductVerified,
			'type' => 'product',
			'title' => s("Produits"),
			'description' => s("Associez un numéro de compte à vos produits et articles"),
		],
		[
			'position' => 2,
			'number' => $data->nPaymentToCheck,
			'numberVerified' => $data->nPaymentVerified,
			'type' => 'payment',
			'title' => s("Moyens de paiement"),
			'description' => s("Renseignez le moyen de paiement des factures"),
		],
	];

	echo '<div class="step-process">';

		foreach($steps as $step) {

	    echo '<a class="step '.($step['number'] > 0 ? '' : 'step-success').' '.($data->type === $step['type'] ? 'selected' : '').'"  href="'.$t->canonical.'?type='.$step['type'].'&from='.$data->search->get('from').'&to='.$data->search->get('to').'">';

			echo '<div class="step-header">';

				echo '<span class="step-number">'.($step['position']).'</span>';

				echo '<div class="step-main">';

				echo '<div class="step-title">';
					echo $step['title'];

					if($step['number'] > 0) {
						echo '<span class="bg-warning tab-item-count ml-1" title="'.s("À contrôler").'">'.$step['numberVerified'].'  / '.($step['number'] + $step['numberVerified']).'</span>';
					}

				echo '</div>';

				echo '<div class="step-value">';

				echo '</div>';

			echo '</div>';

	      echo '</div>';

		    echo '<p class="step-desc hide-sm-down">';
		      echo $step['description'];
		    echo '</p>';

		  echo '</a>';

		}

		echo '<a class="step '.($data->type === 'export' ? 'selected' : '').'" href="'.$t->canonical.'?type=export&from='.$data->search->get('from').'&to='.$data->search->get('to').'">';
			echo '<div class="step-header">';
				echo '<span class="step-number">'.(count($steps) + 1).'</span>';
				echo '<div class="step-main">';
					echo '<div class="step-title">'.s("Intégration des factures en comptabilité").'</div>';
					echo '<div class="step-value"></div>';
				echo '</div>';
			echo '</div>';
			echo '<p class="step-desc">';
				echo s("Exportez un fichier {value} ou créez les écritures comptables de vos factures sur le logiciel comptable de {siteName}", '<span class="util-badge bg-primary">FEC</span>');
			echo '</p>';
		echo '</a>';

	echo '</div>';

	switch($data->type) {

		case 'product':
			echo '<div data-step="'.$data->type.'" class="stick-md util-overflow-md">';
				echo new \preaccounting\PreaccountingUi()->products(
					$data->eFarm,
					$data->cProduct,
					$data->cCategories,
					$data->products,
					$data->search,
					itemData: ['nToCheck' => $data->nItemToCheck, 'cItem' => $data->cItem],
				);
			echo '</div>';
			break;

		case 'payment':
			echo '<div data-step="'.$data->type.'" class="stick-md util-overflow-md">';
				echo new \preaccounting\PreaccountingUi()->invoices($data->eFarm, $data->cInvoice, $data->cPaymentMethod, $data->search);
			echo '</div>';
			break;

		case 'export':
			echo new \preaccounting\PreaccountingUi()->export($data->eFarm, $data->nProductToCheck + $data->nItemToCheck,  $data->nPaymentToCheck, $data->isSearchValid, $data->search);
			break;
	}

});
new AdaptativeView('/precomptabilite/ventes', function($data, FarmTemplate $t) {

	Asset::js('preaccounting', 'preaccounting.js');

	$t->title = s("Précomptabilité des ventes de {value}", $data->eFarm['name']);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite/ventes';

	$t->nav = 'preaccounting';

	if($data->eFarm['hasFinancialYears']) {
		$mainTitle = new \farm\FarmUi()->getAccountingYears($data->eFarm);
	} else {
		$mainTitle = '';
	}

	$mainTitle .= '<div class="util-action">';
		$mainTitle .= '<h1>';
			$mainTitle .= s("Exporter les données des ventes");
		$mainTitle .= '</h1>';

		$mainTitle .= '<div>';
			$mainTitle .= '<a href="/doc/accounting" class="btn btn-xs btn-outline-primary">'.\Asset::icon('person-raised-hand').' '.s("Aide").'</a>';
		$mainTitle .= '</div>';

	$mainTitle .= '</div>';
	$t->mainTitle = $mainTitle;

	echo new \preaccounting\PreaccountingUi()->getSearch($data->eFarm, $data->search, 'sales', count($data->operations) > 0);

	if(count($data->operations) > 0) {

		$totalDebit = array_sum(array_column($data->operations, \preaccounting\AccountingLib::FEC_COLUMN_DEBIT));
		$totalCredit = array_sum(array_column($data->operations, \preaccounting\AccountingLib::FEC_COLUMN_CREDIT));

		echo '<ul class="util-summarize">';

			if($data->search->get('hasInvoice') !== NULL) {

				echo '<li>';
					echo '<a>';
						echo '<h5>'.s("Ventes").'</h5>';
						echo '<div>'.($data->search->get('hasInvoice') === 1 ? s("Facturées") : s("Non facturées")).'</div>';
					echo '</a>';
				echo '</li>';

			}

			echo '<li>';
				echo '<a>';
					echo '<h5>'.p("Écriture", "Écritures", count($data->operations)).'</h5>';
					echo '<div>'.count($data->operations).'</div>';
				echo '</a>';
			echo '</li>';

			echo '<li>';
				echo '<a>';
					echo '<h5>'.p("Vente", "Ventes", $data->nSale).'</h5>';
					echo '<div>'.$data->nSale.'</div>';
				echo '</a>';
			echo '</li>';

			echo '<li>';
				echo '<a>';
					echo '<h5>'.s("Total").'</h5>';
					echo '<div>'.\util\TextUi::money(round($totalCredit - $totalDebit, 2)).'</div>';
				echo '</a>';
			echo '</li>';

		echo '</ul>';


		echo new \preaccounting\SaleUi()->list($data->eFarm, $data->operations);

	} else {

		if($data->search->empty(['id'])) {

			echo '<div class="util-info">';
				echo s("Il n'y a aucune donnée comptable à afficher.");
			echo '</div>';

		} else {

			echo '<div class="util-empty">';
				echo s("Aucune vente ne correspond à vos critères de recherche.");
			echo '</div>';

		}

	}

});

new AdaptativeView('/precomptabilite:importer', function($data, FarmTemplate $t) {

	$t->nav = 'accounting';
	$t->subNav = 'operations';

	$t->title = s("Importer les factures de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:importer';

	$navigation = '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite"  class="h-back">'.\Asset::icon('arrow-left').'</a>';
	$maintitle = new \farm\FarmUi()->getAccountingYears($data->eFarm);
	$maintitle .= '<h1>'.$navigation.s("Importer les factures").($data->nInvoice > 0 ? '<span class="util-counter ml-1">'.$data->nInvoice.'</span>' : '').'</h1>';
	$t->mainTitle = $maintitle;

	echo new \preaccounting\ImportUi()->list($data->eFarm, $data->eFarm['eFinancialYear'], $data->cInvoice, $data->nInvoice, $data->search);

});

new AdaptativeView('/precomptabilite:rapprocher', function($data, FarmTemplate $t) {

	$t->nav = 'bank';

	$t->title = s("Rapprocher factures et opérations bancaires de {farm}", ['farm' => encode($data->eFarm['name'])]);
	$t->canonical = \company\CompanyUi::urlFarm($data->eFarm).'/precomptabilite:rapprocher';
	$navigation = '<a href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/operations" class="h-back">'.\Asset::icon('arrow-left').'</a>';
	$t->mainTitle = '<h1>'.$navigation.s("Rapprocher factures et opérations bancaires").($data->countsByInvoice > 0 ? '<span class="util-counter ml-1">'.$data->countsByInvoice.'</span>' : '').'</h1>';

	if($data->ccSuggestion->empty()) {

		echo '<div class="util-empty">'.s("Il n'y a aucune facture à rapprocher pour le moment !").'</div>';

		echo '<div class="util-block-important">';
			echo \Asset::icon('fire', ['class' => 'util-block-icon']);

			echo '<p>';

			if($data->eImportLast->empty()) {

				echo s("Vous n'avez pas encore réalisé d'import bancaire.");

			} else {

				echo s("Votre dernier import bancaire a permis de rapprocher vos factures jusqu'au {date}.", ['date' => \util\DateUi::numeric($data->eImportLast['endDate'], \util\DateUi::DATE)]);

			}

			echo '</p>';

			if($data->eImportLast->empty()) {
				echo '<a class="btn btn-transparent" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/imports:import">'.s("Importer un premier relevé bancaire").'</a>';
			} else {
				echo '<a class="btn btn-transparent" href="'.\company\CompanyUi::urlFarm($data->eFarm).'/banque/imports:import">'.s("Importer un relevé bancaire").'</a>';
			}

		echo '</div>';

	} else {

		echo new \preaccounting\ReconciliateUi()->list($data->eFarm, $data->ccSuggestion, $data->cMethod);

	}

});

